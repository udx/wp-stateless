<?php
/**
 * System Info class
 *
 * @since 4.0.3
 */

namespace wpCloud\StatelessMedia\Status;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Helper;

class Info {
  use Singleton;

  protected function __construct() {
    StatelessInfo::instance();
    GoogleCloudInfo::instance();

    $this->_init_hooks();
  }

  private function _init_hooks() {
    add_action('wp_ajax_stateless_check_ajax', [$this, 'check_ajax']);

    add_action('wp_stateless_status_tab_content', [$this, 'tab_content'], 10);

    add_filter('wp_stateless_status_tab_visible', array($this, 'status_tab_visible'), 10, 1);

    add_filter('wp_stateless_status_info_values_server', [$this, 'get_server_values'], 10);
    add_filter('wp_stateless_status_info_values_server', [$this, 'get_php_values'], 20);
    add_filter('wp_stateless_status_info_values_server', [$this, 'get_php_modules'], 30);

    add_filter('wp_stateless_status_info_values_wordpress', [$this, 'get_wordpress_network_values'], 10);
    add_filter('wp_stateless_status_info_values_wordpress', [$this, 'get_wordpress_attachments'], 20);
    add_filter('wp_stateless_status_info_values_wordpress', [$this, 'get_wordpress_theme'], 30);
    add_filter('wp_stateless_status_info_values_wordpress', [$this, 'get_wordpress_plugins'], 40);
  }

  /**
   * Check AJAX requests are working
   */
  public function check_ajax() {
    if ( !check_ajax_referer('stateless_check_ajax') ) {
      wp_send_json_error([
        'status' => 'Error',
        'message' => __('Invalid nonce', ud_get_stateless_media()->domain),
      ]);
    }

    wp_send_json_success([
      'status' => 'Ok',
    ]);
  }

  /**
   * Get boolean value (Yes/No)
   * 
   * @param bool $value
   * @return string
   */
  private function _get_bool_value($value) {
    return (bool) $value ? __('Yes', ud_get_stateless_media()->domain) : __('No', ud_get_stateless_media()->domain);
  }

  /**
   * Get server values
   * 
   * @return array
   */
  public function get_server_values($values = []) {
    global $wpdb;

    $server_architecture = function_exists( 'php_uname' ) 
    ? [ php_uname('s'), php_uname('r'), php_uname('m') ]
    : [ __('Unknown', ud_get_stateless_media()->domain) ];

    $rows = [
      'server_architecture' => [
        'label' => __('Server architecture', ud_get_stateless_media()->domain),
        'value' => implode(' ', $server_architecture),
      ],
      'web_server' => [
        'label' => __('Web server', ud_get_stateless_media()->domain),
        'value' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : __('Unknown', ud_get_stateless_media()->domain),
      ],
      'mysql' => [
        'label' => __('MySQL version', ud_get_stateless_media()->domain),
        'value' => $wpdb->db_server_info(),
      ],
    ];

    // Detect the default DB engine
    try {
      $default_engine = '';
      $engines = $wpdb->get_results('SHOW ENGINES', ARRAY_A);

      foreach ($engines as $engine) {
        if ( $engine['Support'] === 'DEFAULT' ) {
          $default_engine = $engine['Engine'];
          break;
        }
      }

      if ( !empty($default_engine) ) {
        $rows['mysql_engine'] = [
          'label' => __('MySQL default engine', ud_get_stateless_media()->domain),
          'value' => $default_engine,
        ];
      }
    } catch (\Throwable $e) {
    }

    $rows['php'] = [
      'label' => __('PHP Version', ud_get_stateless_media()->domain),
      'value' => PHP_VERSION,
    ];

    return $values + $rows;
  }

  /**
   * Get PHP ini values
   * 
   * @return array
   */
  public function get_php_values($values = []) {
    if ( !function_exists('ini_get') ) {
      $values['php_ini_get'] = [
        'label' => __('PHP Config', ud_get_stateless_media()->domain),
        'value' => __('Unable to determine some settings, init_get() is disabled.', ud_get_stateless_media()->domain),
      ];

      return $values;
    }

    $rows = [
      'php_memory_limit' => [
        'label' => __('PHP Memory Limit', ud_get_stateless_media()->domain),
        'value' => ini_get('memory_limit'),
      ],
      'php_max_input_vars' => [
        'label' => __('PHP Max Input Vars', ud_get_stateless_media()->domain),
        'value' => ini_get('max_input_vars'),
      ],
      'php_max_post_size' => [
        'label' => __('PHP Max Post Size', ud_get_stateless_media()->domain),
        'value' => ini_get('post_max_size'),
      ],
      'php_time_limit' => [
        'label' => __('PHP Time Limit', ud_get_stateless_media()->domain),
        'value' => ini_get('max_execution_time'),
      ],
      'max_upload_size' => [
        'label' => __('Max Upload Size', ud_get_stateless_media()->domain),
        'value' => ini_get('upload_max_filesize'),
      ],
      'allow_url_fopen' => [
        'label' => __('Allow URL-aware fopen Wrappers', ud_get_stateless_media()->domain),
        'value' => $this->_get_bool_value( ini_get('allow_url_fopen') ),
      ],
    ];

    return $values + $rows;
  }

  /**
   * Get PHP modules
   * 
   * @return array
   */
  public function get_php_modules($values = []) {
    $values['extensions'] = [
      'label' => __('Loaded Extensions', ud_get_stateless_media()->domain),
      'value' => implode(', ', get_loaded_extensions()),
    ];

    return $values;
  }

  /**
   * Get WP global network values
   * 
   * @return array
   */
  public function get_wordpress_network_values($values = []) {
    $rows = [
      'home_url' => [
        'label' => __('Home URL', ud_get_stateless_media()->domain),
        'value' => get_bloginfo( 'url' ),
      ],
      'site_url' => [
        'label' => __('Site URL', ud_get_stateless_media()->domain),
        'value' => get_bloginfo( 'wpurl' ),
      ],
      'version' => [
        'label' => __('Version', ud_get_stateless_media()->domain),
        'value' => get_bloginfo('version'),
      ],
      'multisite' => [
        'label' => __('Multisite', ud_get_stateless_media()->domain),
        'value' => $this->_get_bool_value( is_multisite() ),
      ],
      'memory_limit' => [
        'label' => __('Memory Limit', ud_get_stateless_media()->domain),
        'value' => WP_MEMORY_LIMIT,
      ],
    ];

    return $values + $rows;
  }

  /**
   * Format WP theme value
   * 
   * @param \WP_Theme $theme
   * @return string|null
   */
  private function _format_theme_value($theme) {
    if ( !is_a($theme, '\WP_Theme') ) {
      return null;
    }

    $value = [$theme->name, $theme->version];

    return implode(' ', $value);
  }

  /**
   * Get WP attachments info
   * 
   * @return array
   */
  public function get_wordpress_attachments($values = []) {
    if ( is_network_admin() ) {
      return $values;
    }

    global $wpdb;
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment'");

    $size_keys = get_intermediate_image_sizes();
    $sizes = [];

    foreach ($size_keys as $size) {
      // Check if image size is not a name (e.g. '1536x1536')
      $parts = explode('x', $size);

      if ( count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]) ) {
        $sizes[] = $size;
        continue;
      }

      $width = get_option("{$size}_size_w");
      $height = get_option("{$size}_size_h");
      $sizes[] = sprintf('(%dx%d) %s', $width, $height, $size);
    }

    $rows = [
      'total_attachments' => [
        'label' => __('Total Attachments', ud_get_stateless_media()->domain),
        'value' => $total,
      ],
      'image_sizes' => [
        'label' => sprintf( __('Image Sizes (%d)', ud_get_stateless_media()->domain), count($sizes) ),
        'value' => implode(', ', $sizes)
      ],
    ];

    return $values + $rows;
  }

  /**
   * Get WP theme values
   * 
   * @return array
   */
  public function get_wordpress_theme($values = []) {
    if ( is_network_admin() ) {
      return $values;
    }

    $theme = wp_get_theme();

    if ( !is_a($theme, '\WP_Theme') ) {
      return $values;
    }

    $rows = [
      'theme' => [
        'label' => __('Theme', ud_get_stateless_media()->domain),
        'value' => $this->_format_theme_value($theme),
      ],
    ];

    $parent = $theme->parent();

    if ( is_a($parent, '\WP_Theme') ) {
      $rows['parent_theme'] = [
        'label' => __('Parent Theme', ud_get_stateless_media()->domain),
        'value' => $this->_format_theme_value($parent),
      ];
    }

    return $values + $rows;
  }

  /**
   * Format WP plugin value
   * 
   * @param array $plugin
   * @param bool $is_network
   * @return string
   */
  private function _format_plugin_value($plugin, $is_network = false) {
    $value = [$plugin['Name'], $plugin['Version']];

    if ($is_network) {
      $value[] = __('(network)', ud_get_stateless_media()->domain);
    }

    return implode(' ', $value);
  }

  /**
   * Get WP plugins list
   * 
   * @return array
   */
  public function get_wordpress_plugins($values = []) {
    if ( is_network_admin() ) {
      return $values;
    }

    $result = [];

    $plugins = get_plugins();
    $active_plugins = (array) get_option( 'active_plugins', [] );
    $network_plugins = (array) get_site_option( 'active_sitewide_plugins', [] );
    $network_plugins = array_keys($network_plugins);

    foreach ($plugins as $file => $plugin) {
      if ( in_array($file, $network_plugins) ) {
        $result[] = $this->_format_plugin_value($plugin, true);

        continue;
      }

      if ( in_array($file, $active_plugins) ) {
        $result[] = $this->_format_plugin_value($plugin);
      }
    }
  
    $values['active_plugins'] = [
      'label' => __('Active Plugins', ud_get_stateless_media()->domain),
      'value' => implode(', ', $result),
    ];

    return $values;
  }

  /**
   * Get section values
   * 
   * @param string $key
   * @return array|null
   */
  private function _get_section_values($key) {
    try {
      $values = apply_filters("wp_stateless_status_info_values_$key", []);
    } catch (\Throwable $e) {
      $values = [
        'error' => [
          'label' => __('Error', ud_get_stateless_media()->domain),
          'value' => $e->getMessage(),
        ],
      ];
    }

    return empty($values) ? null : Helper::array_of_objects($values);
  }

  private function _prepare_copy_text($sections) {
    $text = '';

    foreach ($sections as $section) {
      $text .= '### ' . $section->title . PHP_EOL . PHP_EOL;

      foreach ($section->rows as $row) {
        $text .= sprintf('%s: %s%s',
          strip_tags($row->label),
          strip_tags($row->value),
          PHP_EOL
        );
      }

      $text .= PHP_EOL;
    }

    return sprintf('```%s%s```', PHP_EOL, $text);
  }

  /**
   * Outputs 'Info' section content on Status tab.
   */
  public function tab_content() {
    $sections = [
      'server' => [
        'title' => __('Server', ud_get_stateless_media()->domain),
      ],
      'wordpress' => [
        'title' => __('WordPress', ud_get_stateless_media()->domain),
      ],
      'stateless' => [
        'title' => __('WP-Stateless', ud_get_stateless_media()->domain),
      ],
      'google_cloud' => [
        'title' => __('Google Cloud', ud_get_stateless_media()->domain),
      ],
    ];

    foreach ($sections as $key => $section) {
      $rows = $this->_get_section_values($key);

      if ( !empty($rows) ) {
        $sections[$key]['rows'] = $rows;
      } else {
        unset($sections[$key]);
      }
    }

    $sections = Helper::array_of_objects($sections);
    $copy_text = $this->_prepare_copy_text($sections);

    include ud_get_stateless_media()->path('static/views/status-sections/info.php', 'dir');
  }

  /**
   * Load 'Status' tab only in admin area
   */
  public function status_tab_visible($visible) {
    return is_admin();
  }
}
