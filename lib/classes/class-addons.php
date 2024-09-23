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
     * Addons slugs list.
     *
     * @var array
     */
    protected $addon_ids = [];

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
        $this->addons     = require_once($addons_file);
        $this->addon_ids  = array_keys($this->addons);
      }

      add_action('sm::module::init', array($this, 'check_addons'));
      add_action('sm::module::messages', array($this, 'show_messages'), 10, 3);
      add_action('wp_stateless_addons_tab_content', array($this, 'tab_content'));
      add_filter('wp_stateless_addons_tab_visible', array($this, 'addons_tab_visible'), 10, 1);
      add_filter('wp_stateless_restrict_compatibility', array($this, 'restrict_compatibility'), 10, 3);
      add_filter('wp_stateless_addon_files_root', array($this, 'get_addon_files_root'), 10);
      add_filter('wp_stateless_addon_sync_files_path', array($this, 'get_addon_sync_files_path'), 10, 2);
      add_filter('wp_stateless_addon_files_url', array($this, 'get_addon_files_url'), 10, 2);
    }

    /**
     * Check current theme and installed plugins for recommended addons.
     * Fires hok to display messages if recommended addons are not active.
     * 
     */
    public function check_addons() {
      $active_plugins = Helper::get_active_plugins();

      foreach ($this->addons as $id => $addon) {
        // Recommended theme addons
        if ( isset($addon['theme_name']) ) {
          if ( Helper::is_theme_name($addon['theme_name']) ) {
            $this->addons[$id]['recommended'] = true;
            $this->recommended++;
          }
        }

        // Recommended plugin addons
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

        // Installed addons
        if ( isset($addon['addon_file']) ) {
          if ( file_exists( WP_PLUGIN_DIR . '/' . $addon['addon_file'] ) ) {
            $this->addons[$id]['installed'] = true;
            $this->addons[$id]['activate_link'] = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $addon['addon_file'], 'activate-plugin_' . $addon['addon_file'] );
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
        $button = __('Download Addon', ud_get_stateless_media()->domain);
        $button_link = admin_url('upload.php?page=stateless-settings&tab=stless_addons_tab');
        $message = sprintf(__('Download and activate the WP-Stateless addon for %s to ensure compatibility.', ud_get_stateless_media()->domain), $title);

        if ( isset($addon['activate_link']) ) {
          $button = __('Activate Addon', ud_get_stateless_media()->domain);
          $button_link = $addon['activate_link'];
          $message = sprintf(__('Activate the WP-Stateless addon for %s to ensure compatibility.', ud_get_stateless_media()->domain), $title);
        }

        $plugin_activate_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=classic-widgets/classic-widgets.php', 'activate-plugin_classic-widgets/classic-widgets.php' );

        ud_get_stateless_media()->errors->add([
          'key' => $id,
          'title' => sprintf(__('WP-Stateless: Install the %s Addon', ud_get_stateless_media()->domain), $title),
          'button' => $button,
          'button_link' => $button_link,
          'message' => $message,
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
      $addons = $this->get_addons_view($addons);
      $addons = Helper::array_of_objects($addons);

      include ud_get_stateless_media()->path('static/views/addons-tab.php', 'dir');
    }

    /**
     * Prepare addon data for output.
     * 
     * @param array $addons
     * @return array
     */
    private function get_addons_view($addons) {
      $plugin_desc = __('Provides compatibility between the %s and the WP-Stateless plugins.', ud_get_stateless_media()->domain);
      $theme_desc = __('Provides compatibility between the %s theme and the WP-Stateless plugin.', ud_get_stateless_media()->domain);

      $link = ud_get_stateless_media()->get_docs_page_url('addons/%s');

      $defaults = [
        'title'         => '',
        'icon'          => '',
        'description'   => '',
        'recommended'   => false,
        'active'        => false,
        'installed'     => false,
        'activate_link' => '',
        'hubspot_id'    => '',
        'hubspot_link'  => '',
        'repo'          => '',
        'docs'          => '',
        'link'          => '',
        'wp'            => '',
        'card_class'    => '',
        'status'        => '',
      ];

      foreach ($addons as $id => $addon) {
        if ( isset($addon['theme_name']) ) {
          $addon['description'] = sprintf($theme_desc, $addon['title']);
        } else {
          $addon['description'] = sprintf($plugin_desc, $addon['title']);
        }

        if ( isset($addon['active']) && $addon['active']) {
          $addon['card_class'] = 'active';
          $addon['status'] =  __('active', ud_get_stateless_media()->domain);
        } elseif ( isset($addon['recommended']) && $addon['recommended']) {
          $addon['card_class'] = 'recommended';
          $addon['status'] =  __('recommended', ud_get_stateless_media()->domain);
        }

        $addon['docs'] = sprintf($link, $id);
        $addon['link'] = $addon['docs'];

        $addons[$id] = wp_parse_args($addon, $defaults);
      }

      return $addons;
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

    /**
     * Restrict internal compatibility.
     */
    public function restrict_compatibility($restrict, $id, $is_internal) {
      // If we have a plugin with the same ID as internal compatibility then disable internal compatibility
      if ($is_internal) {
        if ( in_array($id, $this->addon_ids) ) {
          return true;
        }
      }

      return $restrict;
    }

    /**
     * Get the root for saving addons files.
     * In Stateless Mode this will be the root of the bucket.
     * In other modes this will be the uploads base directory.
     */
    public function get_addon_files_root($root_path) {
      if ( ud_get_stateless_media()->is_mode('stateless') ) {
        $root_path = ud_get_stateless_media()->get_gs_path();
      } else {
        $upload_dir = wp_get_upload_dir();
        $root_path = $upload_dir['basedir'];
      }
      
      return $root_path;
    }

    /**
     * Get the root path for syncing addons files.
     * In Stateless and Ephemeral Modes this will be the root of the bucket.
     * In other modes this will be the uploads base directory.
     */
    public function get_addon_sync_files_path($root_path, $addon_folder = '') {
      if ( ud_get_stateless_media()->is_mode( ['stateless', 'ephemeral'] ) ) {
        $root_path = ud_get_stateless_media()->get_gs_path();
      } else {
        $upload_dir = wp_get_upload_dir();
        $root_path = $upload_dir['basedir'];
      }
      
      if ( !empty($addon_folder) ) {
        $root_path = [
          rtrim($root_path, '/'),
          ltrim($addon_folder, '/'),
        ];

        $root_path = implode('/', $root_path);
      }

      return $root_path;
    }

    /**
     * Get the root path for syncing addons files.
     * In Stateless and Ephemeral Modes this will be the root of the bucket.
     * In other modes this will be the uploads base directory.
     */
     public function get_addon_files_url($root_url, $addon_folder = '') {
      $root_url = '';

      if ( ud_get_stateless_media()->is_mode( ['disabled', 'backup'] ) ) {
        $upload_dir = wp_get_upload_dir();
        $root_url = $upload_dir['baseurl'];
      } else {
        $root_url = ud_get_stateless_media()->get_gs_host();
      }

      if ( !empty($addon_folder) ) {
        $root_url = [
          rtrim($root_url, '/'),
          ltrim($addon_folder, '/'),
        ];

        $root_url = implode('/', $root_url);
      }

      return $root_url;
    }
  }
}
