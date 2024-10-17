<?php
/**
 * System Info (Stateless section) class
 *
 * @since 4.0.3
 */

namespace wpCloud\StatelessMedia\Status;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Helper;

class StatelessInfo {
  use Singleton;

  /**
   * Stateless settings
   * 
   * @var array|null
   */
  private $settings = null;

  protected function __construct() {
    $this->_init_hooks();
  }

  private function _init_hooks() {
    add_filter('wp_stateless_status_info_values_stateless', [$this, 'get_settings_values'], 10);
    add_filter('wp_stateless_status_info_values_stateless', [$this, 'get_settings_constants'], 20);
    add_filter('wp_stateless_status_info_values_stateless', [$this, 'get_media_stats'], 30);
    add_filter('wp_stateless_status_info_values_stateless', [$this, 'get_migrations'], 40);
    add_filter('wp_stateless_status_info_values_stateless', [$this, 'prepare_values'], 99);

    add_filter('wp_stateless_status_info_stateless_value', [$this, 'format_value'], 10, 3);
    
    add_filter('wp_stateless_status_info_stateless_value_mode', [$this, 'get_mode'], 10);
    add_filter('wp_stateless_status_info_stateless_value_body_rewrite', [$this, 'get_body_rewrite'], 10);
    add_filter('wp_stateless_status_info_stateless_value_bucket', [$this, 'get_is_set'], 10);
    add_filter('wp_stateless_status_info_stateless_value_bucket_accessible', [$this, 'get_yes_no_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_key_json', [$this, 'get_is_set'], 10);
    add_filter('wp_stateless_status_info_stateless_value_cache_control', [$this, 'get_cache_control'], 10);
    add_filter('wp_stateless_status_info_stateless_value_delete_remote', [$this, 'get_enabled_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_root_dir', [$this, 'get_root_dir'], 10);
    add_filter('wp_stateless_status_info_stateless_value_custom_domain', [$this, 'get_is_set'], 10);
    add_filter('wp_stateless_status_info_stateless_value_hashify_file_name', [$this, 'get_enabled_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_dynamic_image_support', [$this, 'get_enabled_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_use_postmeta', [$this, 'get_enabled_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_WP_STATELESS_LEGACY_URL_TO_POSTID', [$this, 'get_yes_no_value'], 10);
    add_filter('wp_stateless_status_info_stateless_value_WP_STATELESS_SKIP_ACL_SET', [$this, 'get_yes_no_value'], 10);
  }

  /**
   * Get settings
   * 
   * @return array
   */
  private function _get_setings() {
    if ( empty($this->settings) ) {
      $this->settings = ud_get_stateless_media()->get('sm');
    }

    return $this->settings;
  }

  /**
   * Format mode setting
   * 
   * @param string $value
   * @return string
   */
  public function get_mode($value) {
    switch ($value) {
      case '':
        $value = __('Don\'t override', ud_get_stateless_media()->domain);
        break;
      case 'disabled':
        $value = __('Disabled', ud_get_stateless_media()->domain);
        break;
      case 'backup':
        $value = __('Backup', ud_get_stateless_media()->domain);
        break;
      case 'cdn':
        $value = __('CDN', ud_get_stateless_media()->domain);
        break;
      case 'ephemeral':
        $value = __('Ephemeral', ud_get_stateless_media()->domain);
        break;
      case 'stateless':
        $value = __('Stateless', ud_get_stateless_media()->domain);
        break;
    }

    return $value;
  } 

  /**
   * Get body_rewrite setting
   * 
   * @param string $value
   * @return string
   */
  public function get_body_rewrite($value) {
    switch ($value) {
      case '':
        $value = __('Don\'t override', ud_get_stateless_media()->domain);
        break;
      case 'false':
        $value = __('Disabled', ud_get_stateless_media()->domain);
        break;
      case 'enable_editor':
        $value = __('Enable Editor', ud_get_stateless_media()->domain);
        break;
      case 'enable_meta':
        $value = __('Enable Meta', ud_get_stateless_media()->domain);
        break;
      case 'true':
        $value = __('Enable Editor & Meta', ud_get_stateless_media()->domain);
        break;
    }

    return $value;
  } 

  /**
   * Format Cache Control setting
   * 
   * @param bool $value
   * @return string
   */
  public function get_cache_control($value) {
    if ( empty($value) ) {
      $value = sprintf( __('Default: %s', ud_get_stateless_media()->domain), ud_get_stateless_media()->get_default_cache_control() );
    }

    return $value;
  }

  /**
   * Format Folder setting
   * 
   * @param bool $value
   * @return string
   */
  public function get_root_dir($value) {
    if ( empty($value) && is_network_admin() ) {
      $value = __('Don\'t override', ud_get_stateless_media()->domain);
    }

    return $value;
  }

  /**
   * Format the value if it is set or not
   * 
   * @param bool $value
   * @return string
   */
  public function get_is_set($value) {
    return (bool) $value ? __('Set', ud_get_stateless_media()->domain) : __('Not set', ud_get_stateless_media()->domain);
  }

  /**
   * Get boolean value (Yes/No)
   * 
   * @param bool $value
   * @return string
   */
  public function get_yes_no_value($value) {
    return (bool) $value ? __('Yes', ud_get_stateless_media()->domain) : __('No', ud_get_stateless_media()->domain);
  }

  /**
   * Get boolean value (Enable/Disabled)
   * 
   * @param bool $value
   * @return string
   */
  public function get_enabled_value($value) {
    switch ($value) {
      case '':
        $value = __('Don\'t override', ud_get_stateless_media()->domain);
        break;
      case 'true':
        $value = __('Enable', ud_get_stateless_media()->domain);
        break;
      case 'false':
        $value = __('Disable', ud_get_stateless_media()->domain);
        break;
    }

    return $value;
  }

  /**
   * Format stateless settings value
   * 
   * @param string $value
   * @param string $key
   * @param array $sm
   * 
   * @return string
   */
  public function format_value($value, $key, $sm) {
    $value = apply_filters("wp_stateless_status_info_stateless_value_$key", $value, $sm);

    $readonly = array_keys( $sm['readonly'] ?? [] );

    if ( in_array($key, $readonly)) {
      $type = $sm['readonly'][$key] ?? '';

      $suffix = '';
      $hint = $sm['strings'][$type] ?? '';

      switch ($type) {
        case 'network':
          $suffix = __('Network', ud_get_stateless_media()->domain);
          break;
        case 'constant':
          $suffix = __('Constant', ud_get_stateless_media()->domain);
          break;
        case 'environment':
          $suffix = __('Environment', ud_get_stateless_media()->domain);
          break;
      }
  
      if ( !empty($suffix) ) {
        $value = sprintf(
          '<div class="stateless-info-table-extended-value">'.
            '<span>%s</span> '.
            '<span title="%s"><em>(</em>%s%s<em>)</em></span>'.
          '</div', 
          $value,
          $hint,
          '<span class="dashicons dashicons-editor-help"></span>',
          $suffix,
        );
      }
    }

    return $value;
  }

  /**
   * Get WP-Stateless settings
   * 
   * @param array $values
   * @return array
   */
  public function get_settings_values($values) {
    $sm = $this->_get_setings();

    $rows = [
      'version' => [
        'label' => __('Version', ud_get_stateless_media()->domain),
        'value' => ud_get_stateless_media()::$version,
      ],
      'db_version' => [
        'label' => __('Database Version', ud_get_stateless_media()->domain),
        'value' => get_option( ud_stateless_db()::DB_VERSION_KEY, '' ),
      ],
      'mode' => [
        'label' => __('Mode', ud_get_stateless_media()->domain),
        'value' => $sm['mode'],
      ],
      'body_rewrite' => [
        'label' => __('File URL Replacement', ud_get_stateless_media()->domain),
        'value' => $sm['body_rewrite'],
      ],
      'body_rewrite_types' => [
        'label' => __('Supported File Types', ud_get_stateless_media()->domain),
        'value' => $sm['body_rewrite_types'],
      ],
      'bucket' => [
        'label' => __('Bucket', ud_get_stateless_media()->domain),
        'value' => !empty($sm['bucket']),
      ],
      'bucket_accessible' => [
        'label' => __('Bucket Accessible', ud_get_stateless_media()->domain),
        'value' => get_transient('sm::is_connected_to_gs'),
      ],
      'key_json' => [
        'label' => __('Service Account JSON', ud_get_stateless_media()->domain),
        'value' => !empty($sm['key_json']),
      ],
      'cache_control' => [
        'label' => __('Cache-Control', ud_get_stateless_media()->domain),
        'value' => $sm['cache_control'],
      ],
      'delete_remote' => [
        'label' => __('Delete GCS File', ud_get_stateless_media()->domain),
        'value' => $sm['delete_remote'],
      ],
      'root_dir' => [
        'label' => __('Folder', ud_get_stateless_media()->domain),
        'value' => $sm['root_dir'],
      ],
      'custom_domain' => [
        'label' => __('Domain', ud_get_stateless_media()->domain),
        'value' => !empty($sm['custom_domain']),
      ],
      'hashify_file_name' => [
        'label' => __('Cache-Busting', ud_get_stateless_media()->domain),
        'value' => $sm['hashify_file_name'],
      ],
      'dynamic_image_support' => [
        'label' => __('Dynamic Image Support', ud_get_stateless_media()->domain),
        'value' => $sm['dynamic_image_support'],
      ],
      'use_api_siteurl' => [
        'label' => __('Use Site URL for REST API Requests', ud_get_stateless_media()->domain),
        'value' => $sm['use_api_siteurl'],
      ],
      'api_status' => [
        'label' => __('REST API Status', ud_get_stateless_media()->domain),
        'value' => '%api_status%',
      ],
      'ajax_status' => [
        'label' => __('AJAX Status', ud_get_stateless_media()->domain),
        'value' => '%ajax_status%',
      ],
      'use_postmeta' => [
        'label' => __('Use Post Meta', ud_get_stateless_media()->domain),
        'value' => $sm['use_postmeta'],
      ],
    ];

    return $values + $rows;
  }

  /**
   * Get constants settings, not available on Settings page
   * 
   * @param array $values
   * @return array
   */
  public function get_settings_constants($values) {
    $sm = $this->_get_setings();

    $constants = [
      'WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE' => __('Upload Chunk Size', ud_get_stateless_media()->domain),
      'WP_STATELESS_SYNC_MAX_BATCH_SIZE' => __('Sync Max Batch Size', ud_get_stateless_media()->domain),
      'WP_STATELESS_LEGACY_URL_TO_POSTID' => __('Change the Bucket Folder (root_dir) after uploading the image', ud_get_stateless_media()->domain),
      'WP_STATELESS_SKIP_ACL_SET' => __('Skip setting file-level ACl when <strong>uniform bucket-level access</strong> is enabled', ud_get_stateless_media()->domain),
    ];

    $rows = [];

    foreach ($constants as $key => $label) {
      if ( !defined($key) ) {
        continue;
      }

      // Mark all constants as readonly 'constant'
      $this->settings['readonly'][$key] = 'constant';

      $rows[$key] = [
        'label' => $label,
        'value' => constant($key),
      ];
    }

    return $values + $rows;
  }

  /**
   * Get media stats
   * 
   * @param array $values
   * @return array
   */
  public function get_media_stats($values) {
    if (is_network_admin()) {
      return $values;
    }

    $rows = [
      'files' => [
        'label' => __('Total Files', ud_get_stateless_media()->domain),
        'value' => ud_stateless_db()->get_total_files(),
      ],
      'file_sizes' => [
        'label' => __('Total File Sizes', ud_get_stateless_media()->domain),
        'value' => ud_stateless_db()->get_total_file_sizes(),
      ],
      'compatibility_files' => [
        'label' => __('Compatibility Files', ud_get_stateless_media()->domain),
        'value' => ud_stateless_db()->get_total_non_media_files(),
      ],
    ];

    return $values + $rows;
  }

  public function prepare_values($values) {
    $sm = $this->_get_setings();

    foreach ($values as $key => $value) {
      $values[$key]['value'] = apply_filters('wp_stateless_status_info_stateless_value', $value['value'], $key, $sm);
    }

    return $values;
  }

  public function get_migrations($values) {
    if (is_network_admin()) {
      return $values;
    }

    $state = apply_filters("wp_stateless_batch_state", [], ['force_migrations' => true]);

    if ( !is_array($state) || !isset( $state['migrations'] ) ) {
      return $value;
    }

    $migrations = [];

    foreach ($state['migrations'] as $key => $migration) {
      $migrations[] = sprintf( '%s: %s', $key, $migration['status_text'] );
    }

    $rows = [
      'migrations' => [
        'label' => __('Data Optimization', ud_get_stateless_media()->domain),
        'value' => implode(', ', $migrations),
      ],
    ];

    return $values + $rows;
  }
}