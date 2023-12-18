<?php

/**
 * Addons manager.
 *
 * Checks if current theme or any of the installed plugins require WP-Stateless addon.
 * Shows message to the user if addon is recommended.
 * Responsible for showing the list of addons on the settings page.
 *
 * @since 3.3.0
 */

namespace wpCloud\StatelessMedia {

  class Addons {
    use Singleton;

    /**
     * Addons list.
     *
     * @var array
     */
    protected $addons = [];

    /**
     * Recommended addons count.
     *
     * @var int
     */
    protected $recommended = 0;

    /**
     * Active addons count.
     *
     * @var int
     */
    protected $active = 0;

    protected function __construct() {
      $addons_file = ud_get_stateless_media()->path('static/data/addons.php', 'dir');

      if ( file_exists($addons_file) ) {
        $this->addons = require_once($addons_file);
      }

      add_action('sm::module::init', array($this, 'check_addons'));
      add_action('sm::module::messages', array($this, 'show_messages'), 10, 3);
      add_action('wp_stateless_addons_tab_content', array($this, 'tab_content'), 10, 1);
      add_filter('wp_stateless_addons_tab_visible', array($this, 'addons_tab_visible'), 10, 1);
    }

    /**
     * Check current theme and installed plugins for recommended addons.
     * Fires hok to display messages if recommended addons are not active.
     * 
     */
    public function check_addons() {
      $active_plugins = Helper::get_active_plugins();

      foreach ($this->addons as $id => $addon) {
        // Theme addons
        if ( isset($addon['theme_name']) ) {
          if ( Helper::is_theme_name($addon['theme_name']) ) {
            $this->addons[$id]['recommended'] = true;
            $this->recommended++;
          }
        }

        // Plugin addons
        if ( isset($addon['plugin_files']) && is_array($addon['plugin_files']) ) {
          foreach ($addon['plugin_files'] as $file) {
            if ( in_array($file, $active_plugins) ) {
              $this->addons[$id]['recommended'] = true;
              $this->recommended++;

              break;
            }
          }
        }

        // Active addons
        if ( isset($addon['addon_file']) ) {
          if ( in_array($addon['addon_file'], $active_plugins) ) {
            $this->addons[$id]['active'] = true;
            $this->active++;
          }
        }
      }

      // Show message if recommended addons are not active
      do_action('sm::module::messages', $this->addons);
    }

    /**
     * Add messages for recommended addons.
     * 
     * @param array $addons
     * @param array $recommended
     * @param array $active
     * 
     */
    public function show_messages($addons) {
      foreach ($addons as $id => $addon) {
        if ( !isset($addon['recommended']) || isset($addon['active']) ) {
          continue;
        }

        $title = $addon['title'];

        ud_get_stateless_media()->errors->add([
          'key' => $id,
          'title' => sprintf(__("%s: Addon for %s is recommended.", ud_get_stateless_media()->domain), ud_get_stateless_media()->name, $title),
          'button' => __("Get Addon", ud_get_stateless_media()->domain),
          'button_link' => admin_url('upload.php?page=stateless-settings&tab=stless_addons_tab'),
          'message' => __("Addon is recommended to ensure the functionality will work properly between <b>{$title}</b> and <b>WP-Stateless</b>.", ud_get_stateless_media()->domain),
        ], 'notice');  
      }
    }

    /**
     * Outputs 'Addons' tab content on the settings page.
     * 
     */
    public function tab_content() {
      // Prepare filters
      $current_filter = isset($_GET['filter']) && !empty($_GET['filter']) ? $_GET['filter'] : 'all';

      $url = ud_get_stateless_media()->get_settings_page_url('?page=stateless-settings') . '&tab=stless_addons_tab&filter=%s';

      $filters = [
        'all' => [
          'title' => __('All <span class="count">(%d)</span>', ud_get_stateless_media()->domain),
          'count' => count($this->addons),
        ],
        'recommended' => [
          'title' => __('Recommended <span class="count">(%d)</span>', ud_get_stateless_media()->domain),
          'count' => $this->recommended,
        ],
        'active' => [
          'title' => __('Active <span class="count">(%d)</span>', ud_get_stateless_media()->domain),
          'count' => $this->active,
        ],
        'inactive' => [
          'title' => __('Inactive <span class="count">(%d)</span>', ud_get_stateless_media()->domain),
          'count' => count($this->addons) - $this->active,
        ],
      ];

      $filters = Helper::array_of_objects($filters);

      // Filter addons
      switch ($current_filter) {
        case 'recommended':
          $addons = array_filter($this->addons, function($addon) {
            return isset($addon['recommended']);
          });
          break;
        case 'active':
          $addons = array_filter($this->addons, function($addon) {
            return isset($addon['active']);
          });
          break;
        case 'inactive':
          $addons = array_filter($this->addons, function($addon) {
            return !isset($addon['active']);
          });
          break;
        default:
          $addons = $this->addons;
          break;
      }

      $addons = $this->sort_addons($addons);
      $addons = Helper::array_of_objects($addons);

      $description = __('Provides compatibility between the %s and the WP-Stateless plugin.', ud_get_stateless_media()->domain);
      $addon_link = 'https://stateless.udx.io/addons/%s';

      include ud_get_stateless_media()->path('static/views/addons-tab.php', 'dir');
    }

    /**
     * Sort addons: 
     * - recommended not active
     * - other
     * - active
     * 
     * @param array $addons
     * @return array
     */
    private function sort_addons($addons) {
      uasort($addons, function($a1, $a2) {
        $c1 = isset($a1['recommended']) ? -1 : 0;
        $c1 += isset($a1['active']) ? 5 : 0;

        $c2 = isset($a2['recommended']) ? -1 : 0;
        $c2 += isset($a2['active']) ? 5 : 0;

        if ( $c1 !== $c2 ) {
          return $c1 > $c2 ? 1 : -1;
        }

        return strcasecmp( $a1['title'], $a2['title'] );
      });

      return $addons;
    }

    /**
     * Check if 'Addons' tab should be visible.
     */
    public function addons_tab_visible($visible) {
      return count($this->addons) > 0;
    }
  }
}