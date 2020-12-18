<?php

namespace wpCloud\StatelessMedia\Sync;

// Require lib classes if not yet available
if (!class_exists('UDX_WP_Async_Request')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-async-request.php', 'dir');
}

if (!class_exists('UDX_WP_Background_Process')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-background-process.php', 'dir');
}

use UDX_WP_Background_Process, JsonSerializable;

/**
 * Generic background process
 */
abstract class BackgroundSync extends UDX_WP_Background_Process implements ISync, JsonSerializable {

  /**
   * Cron Healthcheck interval
   */
  public $cron_interval;

  /**
   * Flag to allow sorting
   */
  protected $allow_sorting = false;

  /**
   * Flag to allow setting the limit
   */
  protected $allow_limit = false;

  /**
   * Storage for emergency memory
   */
  private $emergency_memory = null;

  /**
   * Storage for currently processed item
   */
  protected $currently_processing_item = null;

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

    $this->cron_interval = $this->get_healthcheck_cron_interval();

    // Reserve 1MB of RAM for the fallback action
    $this->emergency_memory = new \SplFixedArray(65536);

    // Register the fallback action to be executed on shutdown
    register_shutdown_function(function () {
      // Free up reserved memory
      $this->emergency_memory = null;

      // Check if we should execute the fallback action
      if (is_null($err = error_get_last())) return;
      if ($err['type'] != E_ERROR) return;
      if (strstr($err['message'], 'memory') === false || strstr($err['message'], 'exhausted') === false) return;
      if (!$this->is_running()) return;
      if (!$this->currently_processing_item) return;

      // If we are here, then we shutdown because of `memory exhausted` error

      // Remove already processed and problem items from the current batch
      $current_batch = $this->get_batch();
      if ($current_batch && $current_batch->data && is_array($current_batch->data)) {
        foreach ($current_batch->data as $key => $item) {
          unset($current_batch->data[$key]);
          if ($item == $this->currently_processing_item) {
            $this->log(sprintf(__('Item skipped: %s. Waiting for process to resume.', ud_get_stateless_media()->domain), $this->currently_processing_item));
            call_user_func([get_class(), 'task'], $this->currently_processing_item);
            break;
          }
        }
        $current_batch->data = array_values($current_batch->data);
      }

      // Update current batch directly to the option
      // because it needs to be updated even if it is empty
      update_site_option($current_batch->key, $current_batch->data);

      // Add notice
      $this->save_process_meta([
        'notice' => sprintf(
          __("Not enough memory to process the following item '%s' %s: %s. Item skipped. Please, try to increase memory limit or use uploading by chunks: <a target=\"_blank\" href=\"https://wp-stateless.github.io/docs/constants/#wp_stateless_media_upload_chunk_size\">How to use WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE setting.</a>", ud_get_stateless_media()->domain),
          is_numeric($this->currently_processing_item) ? get_the_title($this->currently_processing_item) : $this->currently_processing_item,
          is_numeric($this->currently_processing_item) ? "(ID: {$this->currently_processing_item})" : '',
          $err['message']
        )
      ]);

      wp_die();
    });

    parent::__construct();
  }

  /**
   * Maybe process queue (extended)
   *
   * Checks whether data exists within the queue and that
   * the process is not already running.
   */
  public function maybe_handle() {
    // Don't lock up other requests while processing
    session_write_close();

    if ($this->is_process_running()) {
      // Background process already running.
      wp_die();
    }

    if ($this->is_queue_empty()) {
      // No data to process.
      wp_die();
    }

    $this->handle();

    wp_die();
  }

  /**
   * Determine sync healthcheck interval
   */
  protected function get_healthcheck_cron_interval() {
    return (defined('WP_STATELESS_SYNC_HEALTHCHECK_INTERVAL') && is_int(WP_STATELESS_SYNC_HEALTHCHECK_INTERVAL)) ? WP_STATELESS_SYNC_HEALTHCHECK_INTERVAL : 1;
  }

  /**
   * Get option key for STOPPED option
   */
  protected function get_stopped_option_key() {
    return "{$this->action}_stopped";
  }

  /**
   * Determine maximum batch size
   * 
   * @return int Default is 50
   */
  public function get_max_batch_size() {
    return (defined('WP_STATELESS_SYNC_MAX_BATCH_SIZE') && is_int(WP_STATELESS_SYNC_MAX_BATCH_SIZE)) ? WP_STATELESS_SYNC_MAX_BATCH_SIZE : 50;
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
    $this->delete_all();
    update_site_option($this->get_stopped_option_key(), true);
    $this->clear_process_meta();
    $this->log("Stopped");
  }

  /**
   * Determine if process is stopped.
   * 
   * @return bool
   */
  public function is_stopped() {
    $network_id = get_current_network_id();
    wp_cache_delete("$network_id:notoptions", 'site-options');
    return boolval(get_site_option($this->get_stopped_option_key()));
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
    $this->clear_process_meta();
    $this->clear_queue_size();
    delete_site_option($this->get_stopped_option_key());

    if ($admin_email = get_option('admin_email')) {
      $sync_name = strip_tags($this->get_name());
      $site = site_url();
      wp_mail(
        $admin_email,
        sprintf(__('Stateless Sync for %s is Complete', ud_get_stateless_media()->domain), $sync_name),
        sprintf(__("This is a simple notification to inform you that the WP-Stateless plugin has finished a %s synchronization process for %s.\n\nIf you have WP_STATELESS_SYNC_LOG or WP_DEBUG_LOG enabled, check those logs to review any errors that may have occurred during the synchronization process.", ud_get_stateless_media()->domain), $sync_name, $site)
      );
    }
  }

  /**
   * Remember currently processing item
   */
  protected function before_task($item) {
    $this->currently_processing_item = $item;
  }

  /**
   * Common task that should be executed in the end of each subclass task
   */
  protected function task($_) {
    $processedCount = intval($this->get_process_meta('processed'));
    $this->save_process_meta([
      'processed' => ++$processedCount,
      'last_at' => current_time('timestamp')
    ]);
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
   * Process specific notice
   * 
   * @return array|bool
   */
  public function get_process_notice() {
    $notice = $this->get_process_meta('notice');
    if (empty($notice)) return [];
    return [$notice];
  }

  /**
   * Is running?
   */
  public function is_running() {
    return !$this->is_queue_empty() || $this->is_process_running();
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
      'is_running' => $this->is_running(),
      'limit' => ($limit = $this->get_process_meta('limit')) ? $limit : 0,
      'order' => ($order = $this->get_process_meta('order')) ? $order : 'desc',
      'total_items' => $this->get_total_items(),
      'queued_items' => $this->get_queue_size(),
      'processed_items' => ($processed = $this->get_process_meta('processed')) ? $processed : 0,
      'allow_limit' => $this->allow_limit,
      'allow_sorting' => $this->allow_sorting,
      'notice' => $this->get_process_notice()
    ];
  }

  /**
   * Log background process event
   * 
   * @param string $message
   * @return bool TRUE on success or FALSE on failure
   */
  public function log($message) {
    $message = strip_tags(sprintf('Background Sync - %s: %s', $this->get_name(), $message));

    if (is_multisite()) {
      $blog_id = get_current_blog_id();
      $message = sprintf('[Blog %s] %s', $blog_id, $message);
    }

    if (!defined('WP_STATELESS_SYNC_LOG')) {
      return error_log($message);
    }

    return error_log(date('c') . ": $message\n", 3, WP_STATELESS_SYNC_LOG);
  }

  /**
   * Start process.
   * Should be implemented by subclasses.
   */
  abstract public function start();
}
