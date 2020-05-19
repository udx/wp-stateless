<?php
/**
 * Plugin Name: WP Stateless
 * Plugin URI: https://wordpress.org/plugins/wp-stateless/
 *
 * Compatibility Description: Support for Google App Engine
 *
 */

namespace wpCloud\StatelessMedia {

  if(!class_exists('wpCloud\StatelessMedia\GoogleAppEngine')) {

    class GoogleAppEngine extends ICompatibility {
      protected $id = 'google-app-engine';
      protected $title = 'Google App Engine';
      protected $constant = ['WP_STATELESS_COMPATIBILITY_GAE' => 'WP_STATELESS_COMPATIBILITY_GAE'];
      protected $description = 'Ensures compatibility between WordPress media and Google App Engine in Stateless mode.';
      protected $server_constant = 'GAE_VERSION';
      protected $sm_mode_required = 'stateless';

      public function __construct(){
        $modules = get_option('stateless-modules', array());

        if (empty($modules[$this->id])) {
          // Legacy settings
          $this->enabled = get_option('sm_gae', false);
        }

        $this->init();
      }

      public function module_init($sm){

      }


    }

  }

}
