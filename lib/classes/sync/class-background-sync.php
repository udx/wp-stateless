<?php

namespace wpCloud\StatelessMedia\Sync;

// Require lib classes if not yet available
if (!class_exists('UDX_WP_Async_Request')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-async-request.php', 'dir');
}

if (!class_exists('UDX_WP_Background_Process')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-background-process.php', 'dir');
}

/**
 * Generic background process
 */
abstract class BackgroundSync extends \UDX_WP_Background_Process implements ISync, \JsonSerializable {

  /**
   * Extend the construct
   */
  public function __construct() {
    // Support different threads for multisite installations
    $blog_id = get_current_blog_id();
    $this->action = "{$this->action}_{$blog_id}";

    add_filter('wp_stateless_sync_types', function ($classes) {
      $classes[$c = get_called_class()] = $c;
      return $classes;
    });

    parent::__construct();
  }

  /**
   * Determine maximum batch size
   * 
   * @return int Default is 10
   */
  public function get_max_batch_size() {
    return (defined('WP_STATELESS_SYNC_MAX_BATCH_SIZE') && is_int(WP_STATELESS_SYNC_MAX_BATCH_SIZE)) ? WP_STATELESS_SYNC_MAX_BATCH_SIZE : 10;
  }

  /**
   * Get all batches
   * 
   * @param int $limit 0
   * @return array
   */
  public function get_batches($limit = 0) {
    global $wpdb;

    if (empty($limit) || !is_int($limit)) {
      $limit = 0;
    }

    $table        = $wpdb->options;
    $column       = 'option_name';
    $key_column   = 'option_id';
    $value_column = 'option_value';

    if (is_multisite()) {
      $table        = $wpdb->sitemeta;
      $column       = 'meta_key';
      $key_column   = 'meta_id';
      $value_column = 'meta_value';
    }

    $key = $wpdb->esc_like($this->identifier) . '_batch_%';

    $sql = "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			ORDER BY {$key_column} ASC
			";

    if (!empty($limit)) {
      $sql .= " LIMIT {$limit}";
    }

    $items = $wpdb->get_results($wpdb->prepare($sql, $key));

    $batches = [];

    if (!empty($items)) {
      $batches = array_map(
        function ($item) use ($column, $value_column) {
          $batch       = new \stdClass();
          $batch->key  = $item->$column;
          $batch->data = maybe_unserialize($item->$value_column);

          return $batch;
        },
        $items
      );
    }

    return $batches;
  }

  /**
   * Get one top batch
   */
  protected function get_batch() {
    return array_reduce(
      $this->get_batches(1),
      function ($_, $batch) {
        return $batch;
      },
      []
    );
  }

  /**
   * Delete all batches
   * 
   * @return self
   */
  public function delete_all() {
    $batches = $this->get_batches();

    foreach ($batches as $batch) {
      $this->delete($batch->key);
    }

    $this->clear_queue_size();
    return $this;
  }

  /**
   * Stop processing
   */
  public function stop() {
    $this
      ->delete_all()
      ->clear_process_meta()
      ->unlock_process()
      ->clear_scheduled_event();

    wp_die();
  }

  /**
   * Update the whole queue size
   * 
   * @param int $size
   * @return self
   */
  public function update_queue_size($size) {
    $size = intval($size) + $this->get_queue_size();
    update_site_option("{$this->action}_queue_size", $size);
    return $this;
  }

  /**
   * Get current queue size
   */
  public function get_queue_size() {
    return intval(get_site_option("{$this->action}_queue_size", 0));
  }

  /**
   * Clear the queue size 
   * 
   * @return self
   */
  public function clear_queue_size() {
    delete_site_option("{$this->action}_queue_size");
    return $this;
  }

  /**
   * Clear process meta
   * 
   * @return self
   */
  public function clear_process_meta() {
    // Clear limits for future starts
    delete_site_option("{$this->action}_meta");
    return $this;
  }

  /**
   * Save process meta data
   * 
   * @param array $meta
   */
  public function save_process_meta($meta = []) {
    if (!empty($meta)) {
      $existing_meta = get_site_option("{$this->action}_meta", []);
      foreach ($meta as $key => $value) {
        $existing_meta[$key] = $value;
      }
      update_site_option("{$this->action}_meta", $existing_meta);
    }
  }

  /**
   * Get process meta data. All or by the key.
   * 
   * @param string|bool $key
   * @return array|string|null
   */
  public function get_process_meta($name = false) {
    $meta = get_site_option("{$this->action}_meta", []);
    if (false === $name) {
      return $meta;
    }
    return isset($meta[$name]) ? $meta[$name] : null;
  }

  /**
   * Extending save queue method
   *
   * @return $this
   */
  public function save() {
    $batch_size = is_array($this->data) ? count($this->data) : 1;
    $this->update_queue_size($batch_size);
    parent::save();
    $this->data = [];
    return $this;
  }

  /**
   * Extending complete process method
   */
  protected function complete() {
    parent::complete();
    $this->clear_queue_size();
  }

  /**
   * Default name
   * 
   * @return string
   */
  public function get_name() {
    return __('Background Sync', ud_get_stateless_media()->domain);
  }

  /**
   * Default helper window is set to false
   * 
   * @return HelperWindow|bool
   */
  public function get_helper_window() {
    return false;
  }

  /**
   * Convert to json
   * 
   * @return array
   */
  public function jsonSerialize() {
    return [
      'id' => get_called_class(),
      'name' => $this->get_name(),
      'helper' => $this->get_helper_window(),
      'total_items' => $this->get_total_items(),
      'is_running' => !$this->is_queue_empty() && $this->is_process_running(),
      'limit' => ($limit = $this->get_process_meta('limit')) ? $limit : 0
    ];
  }

  /**
   * Log background process event
   * 
   * @param string $message
   * @return bool TRUE on success or FALSE on failure
   */
  public function log($message) {
    $message = sprintf('Background Sync - %s: %s', $this->get_name(), $message);

    if (!defined('WP_STATELESS_SYNC_LOG')) {
      return error_log($message);
    }

    return error_log(date('c') . ": $message\n", 3, WP_STATELESS_SYNC_LOG);
  }
}
