<?php
/**
 * Plugin Name: Smush Image Compression and Optimization
 * Plugin URI: https://wordpress.org/plugins/wp-smushit/
 *
 * Compatibility Description: Ensures compatibility with WPSmash.
 *
 * @todo Compatibility for backup feature
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\WPSmash')) {
        
        class WPSmash extends ICompatibility {
            protected $id = 'wp-smash';
            protected $title = 'WP Smash';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_WPSmash';
            protected $description = 'Ensures compatibility with WP Smash.';
            protected $plugin_file = 'wp-smushit/wp-smush.php';

            public function module_init($sm){
                add_action('wp_smush_image_optimised', array($this, 'image_optimized'), 10, 2);
                // Check if the file not exists for the given path then download
                // Useful in Stateless mode
                add_action( 'smush_file_exists', array( $this, 'maybe_download_file' ), 10, 3 );
            }

            /**
             * Sync image after it's been optimized.
             * 
             * @param int $ID attachment id
             * @param array $stats compression stats
             * 
             * @return null
             */
            public function image_optimized($ID, $stats){
                $metadata = wp_get_attachment_metadata( $attachment_id );
                ud_get_stateless_media()->add_media( $metadata, $ID, true );
            }

            /**
             * If local file don't exists then download it from GCS
             *
             * @param string $file_path Full file path
             * @param string $attachment_id
             * @param array $size_details Array of width and height for the image
             *
             * @return null
             */
            function maybe_download_file( $file_path = '', $attachment_id = '', $size_details = array() ) {
                if ( empty( $file_path ) || empty( $attachment_id ) ) {
                    return;
                }
                //Download if file not exists
                if ( !file_exists( $file_path ) ) {
                    $client = ud_get_stateless_media()->get_client();
                    $metadata = wp_get_attachment_metadata( $attachment_id );
                    if(!empty($metadata['gs_name'])){
                        $gs_name = dirname($metadata['gs_name']) . '/' . basename($file_path);
                        $client->get_media( apply_filters( 'wp_stateless_file_name', $gs_name), true, $file_path );
                    }
                }
            }

        }

    }

}
