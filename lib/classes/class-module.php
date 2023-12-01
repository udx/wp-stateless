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
      self::$modules[$args['id']] = wp_parse_args($args, array('id' => '', 'self' => '', 'title' => '', 'enabled' => false, 'description' => '', 'is_constant' => false, 'is_network' => false, 'is_plugin_active' => false,));
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
  }
}