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
        protected $description = '';

        public function __construct(){
            $this->init();
        }
        
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