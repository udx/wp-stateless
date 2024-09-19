<?php

/**
 * Google App Engine Compatibility
 *
 * Detects is are running on Google App Engine, shows message and automatically enables 'Stateless' mode
 *
 * @since 3.3.0
 */

namespace wpCloud\StatelessMedia {
  if (!class_exists('wpCloud\StatelessMedia\AppEngine')) {

    /**
     * Class AppEngine
     *
     * @package wpCloud\StatelessMedia
     */
    class AppEngine {
      use Singleton;

      public function __construct() {
        add_filter('wp_stateless_is_app_engine', array($this, 'is_app_engine'));

        add_action('admin_init', array($this, 'add_message'));
        add_action('wp_stateless_settings_refresh', array($this, 'after_settings_refresh'));
        
        add_filter('site_option_sm_mode', [$this, 'override_stateless_mode']);
        add_filter('option_sm_mode', [$this, 'override_stateless_mode']);
      }

      /**
       * Checks if we are running on Google App Engine
       * 
       * @return bool
       */
      public function is_app_engine() {
        return isset($_SERVER["GAE_VERSION"]);
      }

      /**
       * Override 'sm_mode' option to 'stateless' if we are running on Google App Engine
       * Do not override in Disabled mode
       * 
       * @param string $value
       * @return string
       */
      public function override_stateless_mode($value) {
        // We load too early to use 'ud_get_stateless_media()->is_mode' or 'ud_get_stateless_media()->get'
        if ( $value === 'disabled' ) {
          return $value;
        }

        if ( apply_filters('wp_stateless_is_app_engine', false) ) {
          return 'stateless';
        }

        return $value;
      }

      /**
       * Make 'sm_mode' option readonly if we are running on Google App Engine
       * 
       * @param \wpCloud\StatelessMedia\Settings $settingsObj
       */
      public function after_settings_refresh($settingsObj) {
        if ( !is_a($settingsObj, 'wpCloud\StatelessMedia\Settings') ) {
          return;
        }

        if ( !apply_filters('wp_stateless_is_app_engine', false) ) {
          return;
        }

        $settingsObj->set('sm.readonly.mode', 'app_engine'); 
      }

      /**
       * Add admin message if we are running on Google App Engine
       */
      public function add_message() {
        if ( !apply_filters('wp_stateless_is_app_engine', false) ) {
          return;
        }

        ud_get_stateless_media()->errors->add(array(
          'key' => 'stateless_app_engine_auto_mode',
          'button' => 'View Settings',
          'button_link' => admin_url('upload.php?page=stateless-settings'),
          'title' => sprintf(__('Stateless Mode Enabled Automatically.', ud_get_stateless_media()->domain)),
          'message' => sprintf(__('We detected that you are running Google App Engine. This platform does not allow you to save files locally,
                              so we have automatically enabled <b>Stateless</b> mode.  
                              In this mode, your files will only be stored on Google Cloud Storage.', ud_get_stateless_media()->domain)
                              ),
        ), 'notice');
      }
    }
  }
}
