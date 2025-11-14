<?php
/**
 * Plugin Name: Contact Form by WPForms – Drag & Drop Form Builder for WordPress
 * Plugin URI: https://wordpress.org/plugins/wpforms-lite/
 *
 * Compatibility Description: Ensures compatibility with WPForms.
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\WPForms')) {
        
        class WPForms extends ICompatibility {
            protected $id = 'wpforms';
            protected $title = 'WPForms';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_WPFORMS';
            protected $description = 'Ensures compatibility with WPForms.';
            protected $plugin_file = ['wpforms-lite/wpforms.php', 'wpforms/wpforms.php'];

            public function module_init($sm){
                // exclude randomize_filename from wpforms page
                if(!empty($_GET['page']) && $_GET['page'] == 'wpforms-builder') {
                    remove_filter( 'sanitize_file_name', array( "wpCloud\StatelessMedia\Utility", 'randomize_filename' ), 10 );
                }
                
            }

        }

    }

}
