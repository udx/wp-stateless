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

  class Module {

    private static $modules = array();

    /**
     * Object initiated on Bootstrap::__construct
     * Save module data on admin_init hook.
     * Initiate all the compatibility modules.
     */
    public function __construct() {
      add_action('admin_init', array($this, 'save_modules'), 1);
      add_filter('wp_stateless_compatibility_tab_visible', array($this, 'compatibility_tab_visible'), 10, 1);
      add_action('wp_stateless_compatibility_tab_content', array($this, 'tab_content'));

      /**
       * Support for Ewww Image Optimizer
       */
      new EWWW();

      /**
       * Support for Imagify
       */
      new Imagify();

      /**
       * Support for LearnDash
       */
      new LearnDash();

      /**
       * Support for Polylang Pro
       */
      new Polylang();

      /**
       * Support for ShortPixel
       */
      new ShortPixel();

      /**
       * Support for Simple Local Avatars
       */
      new SimpleLocalAvatars();

      /**
       * Support for The Events Calendar
       */
      new TheEventsCalendar();

      /**
       * Support for WooCommerce Extra Product Options
       */
      new CompatibilityWooExtraProductOptions();

      /**
       * Support for WPBakery Page Builder
       */
      new WPBakeryPageBuilder();

      /**
       * Support for Smush
       */
      new WPSmush();

    }

    /**
     * Register compatibility modules so that we can ues them in settings page.
     * Called from ICompatibility::init() method.
     */
    public static function register_module($args) {
      if (empty($args['id'])) {
        return;
      }
      
      if (is_bool($args['enabled'])) {
        $args['enabled'] = $args['enabled'] ? 'true' : 'false';
      }

      $defaults = array(
        'id' => '', 
        'self' => '', 
        'title' => '', 
        'enabled' => false, 
        'description' => '', 
        'is_constant' => false, 
        'is_network' => false, 
        'is_plugin_active' => false,
        'is_internal' => false,
      );
      
      self::$modules[$args['id']] = wp_parse_args($args, $defaults);
    }

    /**
     * Return all the registered modules.
     * Used in admin_init in bootstrap class as localize_script.
     */
    public static function get_modules() {
      return self::$modules;
    }

    /**
     * Return all the registered modules.
     * Used in admin_init in bootstrap class as localize_script.
     */
    public static function get_module($id) {
      if (!empty(self::$modules[$id])) {
        return self::$modules[$id];
      }
      return false;
    }

    /**
     * Handles saving module data.
     * Enable or disable modules from Compatibility tab.
     */
    public function save_modules() {
      if (isset($_POST['action']) && $_POST['action'] == 'stateless_modules' && wp_verify_nonce($_POST['_smnonce'], 'wp-stateless-modules')) {
        $modules = !empty($_POST['stateless-modules']) ? $_POST['stateless-modules'] : array();
        $modules = apply_filters('stateless::modules::save', $modules);

        if (is_network_admin()) {
          update_site_option('stateless-modules', $modules);
        } else {
          update_option('stateless-modules', $modules, true);
        }
        wp_redirect($_POST['_wp_http_referer']);
      }
    }

    /**
     * Check if 'Compatibility' tab should be visible.
     */
    public function compatibility_tab_visible($visible) {
      return !empty(self::$modules);
    }

    /**
     * Outputs 'Compatibility' tab content on the settings page.
     * 
     */
    public function tab_content() {
      $modules = self::get_modules();

      foreach ($modules as $id => $module) {
        if ( !$module['is_internal'] ) {
          unset($modules[$id]);
        }
      }

      $modules = Helper::array_of_objects( $modules );

      include ud_get_stateless_media()->path('static/views/compatibility-tab.php', 'dir');
    }

  }
  
}