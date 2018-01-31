<?php
/**
 * Plugin Name: Easy Digital Downloads
 * Plugin URI: https://wordpress.org/plugins/easy-digital-downloads/
 *
 * Compatibility Description: 
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\EDDDownloadMethod')) {
        
        class EDDDownloadMethod extends ICompatibility {
            protected $id = 'edd-download-method';
            protected $title = 'Easy Digital Downloads';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_EDD';
            protected $description = 'Ensures compatibility with the forced download method and WP-Stateless.';
            
            public function __construct(){
                $this->init();
            }

            public function module_init($sm){
                add_action('edd_process_download_headers', array( $this, 'edd_download_method_support' ), 10, 4);
            }

            /**
             * If EDD download method is Forced (direct) and file goes from GCS then make it to be downloaded right away.
             *
             * @param $requested_file
             * @param $download
             * @param $email
             * @param $payment
             */
            public function edd_download_method_support( $requested_file, $download, $email, $payment ) {
                if (!function_exists('edd_is_local_file') || !function_exists('edd_get_file_download_method') ) 
                    return;
                if (edd_get_file_download_method() != 'direct') 
                    return;
                if (!edd_is_local_file($requested_file) && strstr($requested_file, 'storage.googleapis.com')) {
                    header('Content-Type: application/octet-stream');
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-disposition: attachment; filename=\"" . apply_filters('edd_requested_file_name', basename($requested_file)) . "\"");
                    readfile($requested_file);
                    exit;
                }
            }
        }

    }

}
