<?php
/**
 * Theme Name: Divi
 * Theme URI: https://www.elegantthemes.com/gallery/divi/
 *
 * Compatibility Description: Ensures compatibility with Divi themes Builder Export addon.
 * https://github.com/wpCloud/wp-stateless/issues/224
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\Divi')) {
        
        class Divi extends ICompatibility {
            protected $id = 'divi';
            protected $title = 'Divi';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_DIVI';
            protected $description = 'Ensures compatibility with Divi Builder Export.';
            protected $theme_name = 'Divi';

            public function module_init($sm){
                // exclude randomize_filename from wpforms page
                if(wp_doing_ajax() && !empty($_POST['et_core_portability_export']) && $_POST['et_core_portability_export'] == 'et_core_portability_export') {
                    remove_filter( 'sanitize_file_name', array( "wpCloud\StatelessMedia\Utility", 'randomize_filename' ), 10 );
                }
                
            }

        }

    }

}
