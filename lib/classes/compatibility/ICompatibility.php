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
        protected $plugin_file = null;

        public function __construct(){
            $this->init();
        }
        
        /**
         * Checking whether the plugin is active or not.
         * If the plugin_file is specified then check whether plugin is active or not.
         * We can't use is_plugin_active function because it's defined later in init.
         * By default return true.
         */
        public function is_plugin_active(){
            if(!empty($this->plugin_file)){
                if(is_network_admin()){
                    $plugins = get_site_option( 'active_sitewide_plugins');
                    return isset($plugins[$this->plugin_file]);
                }
                else{
                    return in_array( $this->plugin_file, (array) get_option( 'active_plugins', array() ) );
                }
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
            $is_network_override = false;
            if(is_network_admin()){
                $this->enabled = null;
            }

            if (defined($this->constant)) {
                $this->enabled = constant($this->constant);
                $is_constant = true;
            }
            else {
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
                        $is_network_override = true;
                    }
                }
            }

            if(!is_network_admin() && !$this->is_plugin_active()){
                $this->enabled = 'inactive';
            }
            
            Module::register_module(array(
                'id'                    => $this->id,
                'title'                 => $this->title,
                'enabled'               => $this->enabled,
                'description'           => $this->description,
                'is_constant'           => $is_constant,
                'is_network_override'   => $is_network_override,
                'is_plugin_active'      => $this->is_plugin_active(),
                'is_network_admin'      => is_network_admin(),
            ));

            if ($this->enabled && $this->is_plugin_active()) {
                add_action('sm::module::init', array($this, 'module_init'));
            }
        }
    }

 }