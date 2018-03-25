<?php
/**
 * Plugin Name: Easy Digital Downloads
 * Plugin URI: https://wordpress.org/plugins/easy-digital-downloads/
 *
 * Compatibility Description: 
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\Imagify')) {
        
        class Imagify extends ICompatibility {
            protected $id = 'imagify';
            protected $title = 'Imagify';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_IMAGIFY';
            protected $description = 'Ensures compatibility with Imagify compression plugin.';
            protected $plugin_constant = 'IMAGIFY_VERSION';

            public function module_init($sm){
                // We need to remove the regular handler for sync 
                // unless in stateless mode we would remove the attachment before it's get optimized.
                remove_filter( 'wp_update_attachment_metadata', array( "wpCloud\StatelessMedia\Utility", 'add_media' ), 999 );
                add_action( 'after_imagify_optimize_attachment', array($this, 'after_imagify_optimize_attachment'), 10, 2 );
            }

            /**
             * If image size not exist then upload it to GS.
             * 
             * $args = array(
             *      'thumbnail' => $thumbnail,
             *      'p_img_large' => $p_img_large,
             *   )
             */
            public function after_imagify_optimize_attachment($id, $optimized_data){
                $metadata = wp_get_attachment_metadata( $id );
                ud_get_stateless_media()->add_media( $metadata, $id, true );
            }
            
            
        }

    }

}
