<?php
/**
 * Compatibility abstract class
 *
 * Must be extends in every compatibility module.
 * @todo check if the plugin Active or not.
 *
 * @class ICompatibility
 */

namespace wpCloud\StatelessMedia {

    abstract class ICompatibility{
        protected $id = '';
        protected $title = '';
        protected $constant = '';
        protected $enabled = false;
        protected $description = '';
        protected $plugin_constant = null;
        protected $plugin_class = null;

        public function __construct(){
            $this->init();
        }
        
        public function is_plugin_active(){
            if(!empty($this->plugin_constant)){
                return defined($this->plugin_constant) ? true : false;
            }

            if(!empty($this->plugin_class)){
                return class_exists($this->plugin_class) ? true : false;
            }

            return true;
        }
        
        public function init(){
            $is_constant = false;

            if (defined($this->constant) && $this->is_plugin_active()) {
                $this->enabled = constant($this->constant);
                $is_constant = true;
            }
            elseif($this->is_plugin_active()) {
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