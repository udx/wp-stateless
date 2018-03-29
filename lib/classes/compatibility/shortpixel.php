<?php
/**
 * Plugin Name: ShortPixel Image Optimizer
 * Plugin URI: https://wordpress.org/plugins/shortpixel-image-optimiser/
 *
 * Compatibility Description: Shortpixel lazy sync
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\ShortPixel')) {
        
        class ShortPixel extends ICompatibility {

            protected $id = 'shortpixel';
            protected $title = 'ShortPixel Image Optimizer';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_SHORTPIXEL';
            protected $description = 'Ensures compatibility with ShortPixel Image Optimizer.';
            protected $plugin_constant = 'SHORTPIXEL_IMAGE_OPTIMISER_VERSION';

            public function module_init($sm){
                add_action( 'shortpixel_image_optimised', array($this, 'shortpixel_image_optimised') );
                add_filter( 'get_attached_file', array($this, 'fix_missing_file'), 10, 2 );
            }

            /**
             * Try to restore images before compression
             *
             * @param $file
             * @param $attachment_id
             * @return mixed
             */
            public function fix_missing_file( $file, $attachment_id ) {

                /**
                 * If hook is triggered by ShortPixel
                 */
                if ( !$this->hook_from_shortpixel() ) return $file;

                /**
                 * If mode is stateless then we change it to cdn in order images not being deleted before optimization
                 * Remember that we changed mode via global var
                 */
                if ( ud_get_stateless_media()->get( 'sm.mode' ) == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'cdn' );
                    global $wp_stateless_shortpixel_mode;
                    $wp_stateless_shortpixel_mode = 'stateless';
                }

                $upload_dir = wp_upload_dir();
                $meta_data = wp_get_attachment_metadata( $attachment_id );

                /**
                 * Try to get all missing files from GCS
                 */
                if ( !file_exists( $file ) ) {
                    ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $file )), true, $file );
                }

                if ( !empty( $meta_data['sizes'] ) && is_array( $meta_data['sizes'] ) ) {
                    foreach( $meta_data['sizes'] as $image ) {
                        if ( !empty( $image['gs_name'] ) && !file_exists( $file = trailingslashit( $upload_dir[ 'basedir' ] ).$image['gs_name'] ) ) {
                            ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', $image['gs_name']), true, $file );
                        }
                    }
                }

                return $file;
            }

            /**
             * Determine where we hook from
             * We need to do this only for something specific in shortpixel plugin
             *
             * @return bool
             */
            private function hook_from_shortpixel() {
                $call_stack = debug_backtrace();

                if ( !empty( $call_stack ) && is_array( $call_stack ) ) {
                    foreach( $call_stack as $step ) {
                        if ( $step['function'] == 'getURLsAndPATHs' && strpos( $step['file'], 'wp-short-pixel' ) ) {
                            return true;
                        }
                    }
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
            public function shortpixel_image_optimised($id){

                /**
                 * Restore stateless mode if needed
                 */
                global $wp_stateless_shortpixel_mode;
                if ( $wp_stateless_shortpixel_mode == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'stateless' );
                }

                $metadata = wp_get_attachment_metadata( $id );
                ud_get_stateless_media()->add_media( $metadata, $id, true );
            }
            
        }

    }

}
