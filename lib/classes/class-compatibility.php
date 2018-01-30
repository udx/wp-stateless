<?php
/**
 * Compatibility with other plugins.
 *
 * This class serves as compatibility getway.
 * Initiate all compatibility modules.
 *
 * @class Compatibility
 */

namespace wpCloud\StatelessMedia {
    
    class Module{

        private static $modules = array();

        public function __construct(){
            $this->save_modules();

            /**
             * ACF image crop addons compatibility.
             */
            new CompatibilityAcfImageCrop();
            
            /**
             * Support for Easy Digital Downloads download method
             */
            new EDDDownloadMethod();
        }

        public static function register_module($id, $title , $description, $enabled = false){
            self::$modules[] = array(
                'id'            => $id,
                'title'         => $title,
                'enabled'       => $enabled,
                'description'   => $description,
            );
        }

        public static function get_modules(){
            return self::$modules;
        }

        /**
         * Handles saving module data.
         */
        public function save_modules(){
            if (isset($_POST['action']) && $_POST['action'] == 'stateless_modules' && wp_verify_nonce($_POST['_smnonce'], 'wp-stateless-modules')) {
                $modules = !empty($_POST['stateless-modules']) ? $_POST['stateless-modules'] : array();
                $modules = apply_filters('stateless::modules::save', $modules);
                
                update_option('stateless-modules', $modules, true);
            }
        }
    }

    abstract class ICompatibility{
        const ID = '';
        const TITLE = '';
        const DESCRIPTION = '';
    }

 }