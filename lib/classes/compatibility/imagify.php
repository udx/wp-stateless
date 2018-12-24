<?php
/**
 * Plugin Name: Imagify
 * Plugin URI: https://wordpress.org/plugins/imagify/
 *
 * Compatibility Description: Enables support for these Imagify Image Optimizer features: 
 * auto-optimize images on upload, bulk optimizer, resize larger images, optimization levels (normal, aggressive, ultra).
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\Imagify')) {
        
        class Imagify extends ICompatibility {
            protected $id = 'imagify';
            protected $title = 'Imagify Image Optimizer';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_IMAGIFY';
            protected $description = 'Enables support for these Imagify Image Optimizer features: auto-optimize images on upload, bulk optimizer, resize larger images, optimization levels (normal, aggressive, ultra).';
            protected $plugin_file = 'imagify/imagify.php';

            public function module_init($sm){
                // We need to remove the regular handler for sync 
                // unless in stateless mode we would remove the attachment before it's get optimized.
                remove_filter( 'wp_update_attachment_metadata', array( "wpCloud\StatelessMedia\Utility", 'add_media' ), 999 );
                // @todo add media button returns local url.
                add_filter( 'wp_update_attachment_metadata', array( $this, 'add_media_wrapper' ), 999, 2 );

                add_filter( 'before_imagify_optimize_attachment', array($this, 'fix_missing_file'), 10);
                add_action( 'after_imagify_optimize_attachment', array($this, 'after_imagify_optimize_attachment'), 10 );

                add_filter( 'before_imagify_restore_attachment', array($this, 'get_image_from_gcs'), 10);
                add_action( 'after_imagify_restore_attachment', array($this, 'after_imagify_optimize_attachment'), 10 );
                // Sync from sync tab
                add_action( 'sm:synced::image', array( $this, 'get_image_from_gcs') );
                
            }

            /**
             * @todo
             * Replacement for default wp_update_attachment_metadata filter of bootstrap class.
             * To avoid sync same image twice, once on upload and again after optimization.
             * We also avoid downloading image before optimization on stateless mode.
             */
            public function add_media_wrapper($metadata, $attachment_id){
                $imagify = new \Imagify_Attachment($attachment_id);

                if ( is_callable( array( $imagify, 'is_extension_supported' ) ) ) {
                    if ( ! $imagify->is_extension_supported() ) {
                        return ud_get_stateless_media()->add_media( $metadata, $attachment_id );
                    }
                } elseif ( function_exists( 'imagify_is_attachment_mime_type_supported' ) ) {
                    // Use `imagify_is_attachment_mime_type_supported( $attachment_id )`.
                    if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
                        return ud_get_stateless_media()->add_media( $metadata, $attachment_id );
                    }
                } elseif(!wp_attachment_is_image($attachment_id)){
                    return ud_get_stateless_media()->add_media( $metadata, $attachment_id );
                }

                return $metadata;
            }

            /**
             * Try to restore images before compression
             *
             * @param $file
             * @param $attachment_id
             * @return mixed
             */
            public function fix_missing_file( $attachment_id ) {
                /**
                 * If hook is triggered by ShortPixel
                 */
                if ( !$this->hook_from_imagify($attachment_id) ) return;

                /**
                 * If mode is stateless then we change it to cdn in order images not being deleted before optimization
                 * Remember that we changed mode via global var
                 */
                if ( ud_get_stateless_media()->get( 'sm.mode' ) == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'cdn' );
                    global $wp_stateless_imagify_mode;
                    $wp_stateless_imagify_mode = 'stateless';
                }

                $upload_basedir = wp_upload_dir();
                $upload_basedir = trailingslashit( $upload_basedir[ 'basedir' ] );
                $meta_data = wp_get_attachment_metadata( $attachment_id );
                $file = $upload_basedir . $meta_data['file'];
                
                /**
                 * Try to get all missing files from GCS
                 */
                if ( !file_exists( $file ) ) {
                    ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', $meta_data['file']), true, $file );
                }

                if ( !empty( $meta_data['sizes'] ) && is_array( $meta_data['sizes'] ) ) {
                    foreach( $meta_data['sizes'] as $image ) {
                        if ( !empty( $image['gs_name'] ) && !file_exists( $file = $upload_basedir . $image['gs_name'] ) ) {
                            ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', $image['gs_name']), true, $file );
                        }
                    }
                }

            }

            /**
             * Determine where we hook from
             * We need to do this only for something specific in shortpixel plugin
             *
             * @return bool
             */
            private function hook_from_imagify($attachment_id) {
                $imagify = new \Imagify_Attachment($attachment_id);
                if(method_exists($imagify, 'is_running')){
                    return $imagify->is_running($attachment_id);
                }
                elseif(get_transient( 'imagify-async-in-progress-' . $attachment_id ) !== false){
                    return true;
                }

                return false;
            }


            /**
             * If image size not exist then upload it to GS.
             * 
             * $args = array(
             *      'thumbnail' => $thumbnail,
             *      'p_img_large' => $p_img_large,
             *   )
             */
            public function after_imagify_optimize_attachment($id){
                /**
                 * Restore stateless mode if needed
                 */
                global $wp_stateless_imagify_mode;
                if ( $wp_stateless_imagify_mode == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'stateless' );
                }

                $metadata = wp_get_attachment_metadata( $id );
                ud_get_stateless_media()->add_media( $metadata, $id, true );

                // Sync backup file with GCS
                if( current_filter() == 'after_imagify_optimize_attachment' && ud_get_stateless_media()->get( 'sm.mode' ) !== 'stateless' ) {
                    $file_path = get_attached_file( $id );
                    $backup_path = get_imagify_attachment_backup_path( $file_path );
                    if(file_exists($backup_path)){
                        $upload_dir = wp_upload_dir();
                        $overwrite = apply_filters( 'imagify_backup_overwrite_backup', false, $file_path, $backup_path );
                        $name = str_replace(trailingslashit( $upload_dir[ 'basedir' ] ), '', $backup_path);
                        $name = apply_filters( 'wp_stateless_file_name', $name);
                        do_action( 'sm:sync::syncFile', $name, $backup_path, $overwrite);
                    }
                }
            }

            /**
             * Restore backup file from GCS if not exist.
             */
            public function get_image_from_gcs($id){
                $file_path = get_attached_file( $id );
                $backup_path = get_imagify_attachment_backup_path( $file_path );
                if(!file_exists($backup_path)){
                    $upload_dir = wp_upload_dir();
                    $name = str_replace(trailingslashit( $upload_dir[ 'basedir' ] ), '', $backup_path);
                    $name = apply_filters( 'wp_stateless_file_name', $name);
                    do_action( 'sm:sync::syncFile', $name, $backup_path, true);
                }
            }
            
            
        }

    }

}
