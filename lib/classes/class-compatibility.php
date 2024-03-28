<?php

namespace wpCloud\StatelessMedia;

/**
 * Compatibility abstract class
 * Must be extends in every compatibility module.
 */
abstract class Compatibility {
  protected $id = '';
  protected $title = '';

  /**
   * // Type String or Array.
   *
   * Single constant:
   * example "WP_STATELESS_COMPATIBILITY_EDD"
   *
   * Multiple constant:
   * [Constant, Another_Constant, ....]
   * ['WP_STATELESS_MEDIA_ON_FLY', 'WP_STATELESS_DYNAMIC_IMAGE_SUPPORT']
   *
   * Deprecated constant:
   * [Old Constant => New Constant, ...]
   * ['WP_STATELESS_MEDIA_ON_FLY' => 'WP_STATELESS_DYNAMIC_IMAGE_SUPPORT']
   */
  protected $constant = '';
  protected $enabled = true;
  protected $description = '';
  protected $plugin_file = null;
  protected $theme_name = null;
  protected $first_party = false;
  protected $non_library_sync = false;
  protected $server_constant = false;
  protected $sm_mode_required = '';
  protected $sm_mode_not_supported = [];
  protected $is_internal = false;

  public function __construct() {
    // Prevent conflict between internal built-in compatibility modules and addon plugins
    $restrict = apply_filters('wp_stateless_restrict_compatibility', false, $this->id, $this->is_internal);
    
    if ($restrict) {
      $this->enabled = false;
      return;
    }

    if ( !$this->is_internal ) {
      add_filter('wp_stateless_restrict_compatibility', array($this, 'restrict_compatibility'), 10, 3);
    }

    $this->init();
  }

  /**
   * Checking whether the plugin is active or not.
   * If the plugin_file is specified then check whether plugin is active or not.
   * We can't use is_plugin_active function because it's defined later in init.
   * By default return true.
   *
   * @todo caching.
   */
  public function is_plugin_active() {
    if (!empty($this->theme_name)) {
      return Helper::is_theme_name($this->theme_name);
    }

    if (!empty($this->plugin_file)) {
      // Converting string to array for foreach
      if (is_string($this->plugin_file)) {
        $this->plugin_file = array($this->plugin_file);
      }

      // If multisite then check if plugin is network active
      if (is_multisite()) {
        $active_plugins = (array)get_site_option('active_sitewide_plugins');
        foreach ($this->plugin_file as $plugin_file) {
          if (isset($active_plugins[$plugin_file])) {
            return true;
          }
        }

        // If we are in network admin then return, unless it will get data from main site.
        if (is_network_admin()) {
          return false;
        }
      }

      $active_plugins = (array)get_option('active_plugins', array());
      foreach ($this->plugin_file as $plugin_file) {
        if (in_array($plugin_file, $active_plugins)) {
          return true;
        }
      }

      return false;
    }

    /**
     * If server constant is set - check if exist it on global $_SERVER
     */
    if (!empty($this->server_constant)) {
      if (isset($_SERVER[$this->server_constant])) {
        return true;
      }
      return false;
    }

    return true;
  }

  /**
   * Checking whether current mode is supported.
   * By default return true.
   *
   * @todo caching.
   */
  public function is_mode_supported() {
    $sm_mode = isset($_POST['sm']['mode']) ? $_POST['sm']['mode'] : ud_get_stateless_media()->get('sm.mode');
    if (in_array($sm_mode, $this->sm_mode_not_supported)) {
      return false;
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
  public function init() {
    $is_constant = false;
    $is_network_override = false;
    $sm_mode = isset($_POST['sm']['mode']) ? $_POST['sm']['mode'] : ud_get_stateless_media()->get('sm.mode');
    if (is_network_admin()) {
      $this->enabled = null;
    }

    if (is_array($this->constant)) {
      foreach ($this->constant as $old_const => $new_const) {
        if (defined($new_const)) {
          $is_constant = true;
          $this->enabled = constant($new_const);
          break;
        }
        if (is_string($old_const) && defined($old_const)) {
          $is_constant = true;
          $this->enabled = constant($old_const);
          ud_get_stateless_media()->errors->add(array(
            'key' => $this->id,
            'title' => sprintf(__("%s: Deprecated Notice (%s)", ud_get_stateless_media()->domain), ud_get_stateless_media()->name, $this->title),
            'message' => sprintf(__("<i>%s</i> constant is deprecated, please use <i>%s</i> instead.", ud_get_stateless_media()->domain), $old_const, $new_const),
          ), 'notice');
          break;
        }
      }
    } elseif (defined($this->constant)) {
      $this->enabled = constant($this->constant);
      $is_constant = true;
    }

    if (!$is_constant) {
      $modules = get_option('stateless-modules', array());
      if (empty($this->enabled)) {
        $this->enabled = !empty($modules[$this->id]) && $modules[$this->id] == 'true' ? true : false;
      }
      if (is_multisite()) {
        $modules = get_site_option('stateless-modules', array());
        if (is_network_admin()) {
          $this->enabled = !empty($modules[$this->id]) ? ($modules[$this->id] == 'true' ? true : false) : '';
        } elseif (!empty($modules[$this->id])) {
          $this->enabled = !empty($modules[$this->id]) ? ($modules[$this->id] == 'true' ? true : false) : '';
          $is_network_override = true;
        }
      }
    }

    if (!is_network_admin() && (!$this->is_plugin_active() || !$this->is_mode_supported())) {
      $this->enabled = 'inactive';
    }

    /**
     * Checking whether to show manual sync option.
     */
    if ($this->is_plugin_active() && $this->non_library_sync == true) {
      global $show_non_library_sync;
      $show_non_library_sync = true;
    }

    Module::register_module(array(
      'id' => $this->id,
      'self' => $this,
      'title' => $this->title,
      'enabled' => $this->enabled,
      'description' => $this->description,
      'is_constant' => $is_constant,
      'is_network_override' => $is_network_override,
      'is_plugin_active' => $this->is_plugin_active(),
      'is_network_admin' => is_network_admin(),
      'is_plugin' => !empty($this->plugin_file),
      'is_theme' => !empty($this->theme_name),
      'is_mode_supported' => $this->is_mode_supported(),
      'mode' => ucfirst($sm_mode),
      'is_internal' => $this->is_internal,
    ));

    if ($this->enabled && $this->is_plugin_active() && $this->is_mode_supported()) {
      add_action('sm::module::init', array($this, 'module_init'));
    }

    if (!$this->enabled && !$this->first_party && $this->is_plugin_active()) {
      ud_get_stateless_media()->errors->add(array(
        'key' => $this->id,
        'title' => sprintf(__("%s: Compatibility for %s isn't enabled.", ud_get_stateless_media()->domain), ud_get_stateless_media()->name, $this->title),
        'button' => __("Enable Compatibility", ud_get_stateless_media()->domain),
        'message' => __("Please enable the compatibility to ensure the functionality will work properly between <b>{$this->title}</b> and <b>WP-Stateless</b>.", ud_get_stateless_media()->domain),
      ), 'notice');
    }

    /**
     * Check requires WP-Stateless mode
     */
    if (!empty($this->sm_mode_required) && $this->enabled !== 'inactive') {
      if ($sm_mode !== $this->sm_mode_required) {
        ud_get_stateless_media()->errors->add(array(
          'key' => $this->id,
          'title' => sprintf(__("%s: Current Mode is not compatible with  %s.", ud_get_stateless_media()->domain), ud_get_stateless_media()->name, $this->title),
          'message' => sprintf(__("%s compatibility requires %s in %s mode.", ud_get_stateless_media()->domain), $this->title, ud_get_stateless_media()->name, ucfirst($this->sm_mode_required)),
        ), 'notice');
      }
    }
  }

  /**
   * @return bool
   */
  public function enable_compatibility() {

    if (is_network_admin()) {
      $modules = get_site_option('stateless-modules', array());
      if (empty($modules[$this->id]) || $modules[$this->id] != 'true') {
        $modules[$this->id] = 'true';
        update_site_option('stateless-modules', $modules, true);
      }
    } else {
      $modules = get_option('stateless-modules', array());
      if (empty($modules[$this->id]) || $modules[$this->id] != 'true') {
        $modules[$this->id] = 'true';
        update_option('stateless-modules', $modules, true);
      }
    }

    return true;
  }

  /**
   * add_webp_mime
   * @param $t
   * @param $user
   * @return mixed
   */
  public function add_webp_mime($t, $user) {
    $t['webp'] = 'image/webp';
    return $t;
  }

  /**
   * Restrict internal compatibility.
   * @param $restrict   bool
   * @param $id         string
   * @param $is_internal bool
   * @return bool
   */
  public function restrict_compatibility($restrict, $id, $is_internal) {
    // If we have internal compatibility with the same ID - then disable internal compatibility
    return $is_internal && $this->id == $id ? true : $restrict;
  }
}
