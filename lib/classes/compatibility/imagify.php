<?php
/**
 * Plugin Name: Imagify
 * Plugin URI: https://wordpress.org/plugins/imagify/
 *
 * Compatibility Description: Enables support for these Imagify Image Optimizer features: 
 * auto-optimize images on upload, bulk optimizer, resize larger images, optimization levels (normal, aggressive, ultra).
 *
 * https://github.com/wpCloud/wp-stateless/issues/206
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\Imagify')) {
        
        class Imagify extends ICompatibility {
            protected $id = 'imagify';
            protected $title = 'Imagify Image Optimizer';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_IMAGIFY';
            protected $description = 'Enables support for these Imagify Image Optimizer features: auto-optimize images on upload, bulk optimizer, resize larger images, optimization levels (normal, aggressive, ultra).';
            protected $plugin_file = ['imagify/imagify.php', 'imagify-plugin/imagify.php'];

            public function module_init($sm){
                // Skip sync on upload when attachment is image, sync will be handled after image is optimized.
                // Disabling for now because it's cause problem.
                // add_filter( 'wp_stateless_skip_add_media', array( $this, 'skip_add_media' ), 10, 5 );
                add_filter( 'before_imagify_optimize_attachment', array($this, 'fix_missing_file'), 10);
                add_action( 'after_imagify_optimize_attachment', array($this, 'after_imagify_optimize_attachment'), 10 );

                // if imagify implement this filter then enable it.
                add_filter( 'imagify_has_backup', array($this, 'imagify_has_backup'), 10, 2);

                add_filter( 'before_imagify_restore_attachment', array($this, 'get_image_from_gcs'), 10);
                add_action( 'after_imagify_restore_attachment', array($this, 'after_imagify_optimize_attachment'), 10 );
                // Sync from sync tab
                add_action( 'sm:synced::image', array( $this, 'get_image_from_gcs') );
                
            }

            /**
             * Whether to skip the sync on image upload before the image is optimized.
             * The sync is skipped if the image is compatible with Smush.
             * 
             * The image will be synced after it's get optimized using the 'wp_smush_image_optimised' action.
             * 
             *
             * @param bool   $return         This should return true if want to skip the sync.
             * @param int    $metadata       Metadata for the attachment.
             * @param string $attachment_id  Attachment ID.
             * @param bool   $force          Whether to force the sync even the file already exist in GCS.
             * @param bool   $args           Whether to only sync the full size image.
             * 
             * @return bool  $return         True to skip the sync and false to do the sync.
             * 
             */
            public function skip_add_media($return, $metadata, $attachment_id, $force = false, $args = array()) {
                global $doing_manual_sync;
                $args = wp_parse_args($args, array(
                    'no_thumb' => false,
                  ));

                if($force || $doing_manual_sync || !get_imagify_option( 'auto_optimize' ) || $args['no_thumb'] == true ) return false;

                $imagify = new \Imagify_Attachment($attachment_id);
                if ( is_callable( array( $imagify, 'is_extension_supported' ) ) ) {
                    if ( ! $imagify->is_extension_supported() ) {
                        return false;
                    }
                } elseif ( function_exists( 'imagify_is_attachment_mime_type_supported' ) ) {
                    // Use `imagify_is_attachment_mime_type_supported( $attachment_id )`.
                    if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
                        return false;
                    }
                } elseif(!wp_attachment_is_image($attachment_id)){
                    return false;
                }

                return true;
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
                    $upload_basedir = trailingslashit( dirname($file) );
                    foreach( $meta_data['sizes'] as $image ) {
                        if ( !empty( $image['gs_name'] ) && !file_exists( $file = $upload_basedir . $image['file'] ) ) {
                            ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', $image['gs_name']), true, $file );
                        }
                    }
                }

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
                if( current_filter() == 'after_imagify_optimize_attachment' ) {
                    /**
                     * If mode is stateless then we change it to cdn in order images not being deleted before optimization
                     * Remember that we changed mode via global var
                     * @todo remove if Imagify implement "imagify_has_backup" filter.
                     */
                    if ( ud_get_stateless_media()->get( 'sm.mode' ) == 'stateless' ) {
                        ud_get_stateless_media()->set( 'sm.mode', 'cdn' );
                        global $wp_stateless_imagify_mode;
                        $wp_stateless_imagify_mode = 'stateless';
                    }
                    
                    $file_path = get_attached_file( $id );
                    $backup_path = get_imagify_attachment_backup_path( $file_path );
                    if(file_exists($backup_path)){
                        $overwrite = apply_filters( 'imagify_backup_overwrite_backup', false, $file_path, $backup_path );
                        // wp_stateless_file_name filter will remove the basedir from the path and prepend with root dir.
                        $name = apply_filters( 'wp_stateless_file_name', $backup_path);
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

            /**
             * Check if backup exists in GCS.
             */
            public function imagify_has_backup($return, $has_backup){
                if(!$return && $has_backup){
                    $name = apply_filters( 'wp_stateless_file_name', $has_backup);
                    $return = (bool) apply_filters( 'sm:sync::queue_is_exists', $name);
                }
                return $return;
            }
            
            
        }

    }

}
