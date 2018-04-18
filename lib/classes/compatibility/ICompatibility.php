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
        
        /**
         * Checking whether the plugin is active or not.
         * If the plugin_constant is specified then check whether plugin_constant is defined or not.
         * If the plugin_class is specified then check whether plugin_class exist or not.
         * 
         * By defult return true.
         */
        public function is_plugin_active(){
            if(!empty($this->plugin_constant)){
                return defined($this->plugin_constant) ? true : false;
            }

            if(!empty($this->plugin_class)){
                return class_exists($this->plugin_class) ? true : false;
            }

            return true;
        }
        
        /**
         * Initialize the module
         * Check whether plugin is active or not.
         * Register module.
         * 
         * Add action for sm::module::init hook for module_init, which is fired(do_action) on Bootstrap::init()
         */
        public function init(){
            $is_constant = false;
            $is_network = false;
            if(is_network_admin()){
                $this->enabled = null;
            }

            if (defined($this->constant) && $this->is_plugin_active()) {
                $this->enabled = constant($this->constant);
                $is_constant = true;
            }
            elseif($this->is_plugin_active()) {
                $modules = get_option('stateless-modules', array());
                if (empty($this->enabled)) {
                    $this->enabled = !empty($modules[$this->id]) && $modules[$this->id] == 'true' ? true : false;
                }
                if(is_multisite()){
                    $modules = get_site_option( 'stateless-modules', array() );
                    if(is_network_admin()){
                        $this->enabled = $modules[$this->id];
                    }
                    elseif(!empty($modules[$this->id])){
                        $this->enabled = $modules[$this->id];
                        $is_network = true;
                    }
                }
            }
            
            Module::register_module(array(
                'id'                => $this->id,
                'title'             => $this->title,
                'enabled'           => $this->enabled,
                'description'       => $this->description,
                'is_constant'       => $is_constant,
                'is_network'        => $is_network,
                'is_plugin_active'  => $this->is_plugin_active(),
            ));

            if ($this->enabled) {
                add_action('sm::module::init', array($this, 'module_init'));
            }
        }
    }

 }