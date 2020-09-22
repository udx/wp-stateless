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
      $classes[] = get_called_class();
      return $classes;
    });

    parent::__construct();
  }

  /**
   * 
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

    $batches = array();

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
   * 
   */
  protected function get_batch() {
    return array_reduce(
      $this->get_batches(1),
      function ($_, $batch) {
        return $batch;
      },
      array()
    );
  }

  /**
   * 
   */
  public function delete_all() {
    $batches = $this->get_batches();

    foreach ($batches as $batch) {
      $this->delete($batch->key);
    }
  }

  /**
   * 
   */
  public function get_name() {
    return __('Background Sync', ud_get_stateless_media()->domain);
  }

  /**
   * 
   */
  public function get_helper_window() {
    return false;
  }

  /**
   * 
   */
  public function jsonSerialize() {
    return [
      'name' => $this->get_name(),
      'helper' => $this->get_helper_window(),
      'total_items' => $this->get_total_items(),
      'is_running' => !$this->is_queue_empty() && $this->is_process_running()
    ];
  }
}
