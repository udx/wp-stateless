<?php
/**
 * Plugin Name: Easy Digital Downloads
 * Plugin URI: https://wordpress.org/plugins/easy-digital-downloads/
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
                // We need to remove the regular handler for sync 
                // unless in stateless mode we would remove the attachment before it's get optimized.
                remove_filter( 'wp_update_attachment_metadata', array( "wpCloud\StatelessMedia\Utility", 'add_media' ), 999 );
                add_action( 'shortpixel_image_optimised', array($this, 'shortpixel_image_optimised') );
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
                $metadata = wp_get_attachment_metadata( $id );
                ud_get_stateless_media()->add_media( $metadata, $id, true );
            }
            
        }

    }

}
