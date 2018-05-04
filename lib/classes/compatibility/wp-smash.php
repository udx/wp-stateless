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

                // We need to remove the regular handler for sync 
                // unless in stateless mode we would remove the attachment before it's get optimized.
                remove_filter( 'wp_update_attachment_metadata', array( "wpCloud\StatelessMedia\Utility", 'add_media' ), 999 );
                add_filter( 'wp_update_attachment_metadata', array( $this, 'add_media_wrapper' ), 999, 2 );
            }

            /**
             * Replacement for default wp_update_attachment_metadata filter of bootstrap class.
             * To avoid sync same image twice, once on upload and again after optimization.
             * We also avoid downloading image before optimization on stateless mode.
             */
            public function add_media_wrapper($metadata, $attachment_id){
                global $wpsmush_settings;
                $auto_smush = $wpsmush_settings->settings['auto'];

                if( !$auto_smush || !wp_attachment_is_image( $attachment_id ) ||
                    !apply_filters( 'wp_smush_image', true, $attachment_id ) || 
                    (
                        (( ! empty( $_POST['action'] ) && 'upload-attachment' == $_POST['action'] ) || isset( $_POST['post_id'] )) &&
                        // And, check if Async is enabled.
                        defined( 'WP_SMUSH_ASYNC' ) && WP_SMUSH_ASYNC
                    )
                ){
                    return ud_get_stateless_media()->add_media( $metadata, $attachment_id );
                }
                return $metadata;
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
