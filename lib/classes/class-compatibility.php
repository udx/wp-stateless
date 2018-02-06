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
             * Dynamic Image Support
             */
            new DynamicImageSupport();

            /**
             * ACF image crop addons compatibility.
             */
            new CompatibilityAcfImageCrop();
            
            /**
             * Support for Easy Digital Downloads download method
             */
            new EDDDownloadMethod();
            
            /**
             * Support for SiteOrigin CSS files
             */
            new SOWidgetCSS();
        }

        public static function register_module($id, $title , $description, $enabled = 'false', $is_constant = false){
            if (is_bool($enabled)) {
                $enabled = $enabled ? 'true' : 'false';
            }
            
            self::$modules[] = array(
                'id'            => $id,
                'title'         => $title,
                'enabled'       => $enabled,
                'description'   => $description,
                'is_constant'   => $is_constant,
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
        protected $id = '';
        protected $title = '';
        protected $constant = '';
        protected $description = '';

        public function init(){
            $is_constant = false;

            if (defined($this->constant)) {
                $this->enabled = constant($this->constant);
                $is_constant = true;
            }
            else {
                $modules = get_option('stateless-modules', array());
                if (empty($this->enabled)) {
                    $this->enabled = !empty($modules[$this->id]) && $modules[$this->id] == 'true' ? true : false;
                }
            }
            
            Module::register_module($this->id, $this->title, $this->description, $this->enabled, $is_constant);

            if ($this->enabled) {
                add_action('sm::module::init', array($this, 'module_init'));
            }
        }
    }

 }