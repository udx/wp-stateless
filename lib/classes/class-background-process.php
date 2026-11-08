<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\Background_Process')) {
  /**
   * Stores background work in WordPress options and drains it in bounded batches.
   *
   * Batches use site options on single sites and network meta on multisite.
   * Processing locks use site transients, and cron healthchecks can drain a queue
   * even when loopback AJAX requests are unavailable.
   */
  abstract class Background_Process {
    protected $prefix = 'wp';
    protected $action = 'background_process';
    protected $identifier;
    protected $data = array();
    protected $start_time = 0;
    protected $cron_hook_identifier;
    protected $cron_interval_identifier;
    protected $allowed_batch_data_classes = true;

    const STATUS_CANCELLED = 1;
    const STATUS_PAUSED = 2;

    /**
     * Registers AJAX and cron handlers for this queue.
     *
     * @param bool|array $allowed_batch_data_classes Classes allowed when unserializing batch data.
     */
    public function __construct($allowed_batch_data_classes = true) {
      $this->identifier = $this->prefix . '_' . $this->action;
      $this->allowed_batch_data_classes = $allowed_batch_data_classes;
      $this->cron_hook_identifier = $this->identifier . '_cron';
      $this->cron_interval_identifier = $this->identifier . '_cron_interval';
      add_action('wp_ajax_' . $this->identifier, array($this, 'maybe_handle'));
      add_action('wp_ajax_nopriv_' . $this->identifier, array($this, 'maybe_handle'));
      add_action($this->cron_hook_identifier, array($this, 'handle_cron_healthcheck'));
      add_filter('cron_schedules', array($this, 'schedule_cron_healthcheck'));
    }

    /**
     * Replaces the queue data awaiting persistence.
     *
     * @param array $data Items to process in later batches.
     * @return Background_Process This queue for chaining.
     */
    public function data($data) {
      $this->data = $data;
      return $this;
    }

    /**
     * Appends one item to the pending queue.
     *
     * @param mixed $data Task payload understood by the concrete task() implementation.
     * @return Background_Process This queue for chaining.
     */
    public function push_to_queue($data) {
      $this->data[] = $data;
      return $this;
    }

    /**
     * Persists pending items in a uniquely named batch and clears local data.
     *
     * @return Background_Process This queue for chaining.
     */
    public function save() {
      $key = $this->generate_key();
      if (!empty($this->data)) update_site_option($key, $this->data);
      $this->data = array();
      return $this;
    }

    /**
     * Replaces an existing batch's data when items remain.
     *
     * @param string $key Existing batch option name.
     * @param array $data Remaining task payloads.
     * @return Background_Process This queue for chaining.
     */
    public function update($key, $data) {
      if (!empty($data)) update_site_option($key, $data);
      return $this;
    }

    /**
     * Deletes one batch option.
     *
     * @param string $key Batch option name.
     * @return Background_Process This queue for chaining.
     */
    public function delete($key) {
      delete_site_option($key);
      return $this;
    }

    /**
     * Deletes every batch and resets the queue's status.
     */
    public function delete_all() {
      foreach ($this->get_batches() as $batch) $this->delete($batch->key);
      delete_site_option($this->get_status_key());
      $this->cancelled();
    }

    /**
     * Schedules recovery and attempts a nonblocking loopback dispatch.
     *
     * @return array|\WP_Error|false Loopback response, HTTP error, or false when already processing.
     */
    public function dispatch() {
      if ($this->is_processing()) return false;
      $this->schedule_event();
      $url = add_query_arg($this->get_query_args(), $this->get_query_url());
      return wp_remote_post(esc_url_raw($url), $this->get_post_args());
    }

    /** @return array Nonce and action arguments for the loopback request. */
    protected function get_query_args() {
      if (property_exists($this, 'query_args')) return $this->query_args;
      return apply_filters($this->identifier . '_query_args', array(
        'action' => $this->identifier,
        'nonce' => wp_create_nonce($this->identifier),
      ));
    }

    /** @return string URL for the loopback request. */
    protected function get_query_url() {
      if (property_exists($this, 'query_url')) return $this->query_url;
      return apply_filters($this->identifier . '_query_url', admin_url('admin-ajax.php'));
    }

    /** @return array WordPress HTTP arguments for the nonblocking dispatch. */
    protected function get_post_args() {
      if (property_exists($this, 'post_args')) return $this->post_args;
      return apply_filters($this->identifier . '_post_args', array(
        'timeout' => 5,
        'blocking' => false,
        'body' => $this->data,
        'cookies' => $_COOKIE,
        'sslverify' => apply_filters('https_local_ssl_verify', false),
      ));
    }

    /**
     * Handles an authenticated AJAX worker request.
     *
     * Queue status is checked before the queue nonce, allowing stopped workers
     * to exit without processing more items.
     */
    public function maybe_handle() {
      session_write_close();
      if ($this->is_processing()) return $this->maybe_wp_die();
      if ($this->is_cancelled()) {
        $this->clear_scheduled_event();
        $this->delete_all();
        return $this->maybe_wp_die();
      }
      if ($this->is_paused()) {
        $this->clear_scheduled_event();
        $this->paused();
        return $this->maybe_wp_die();
      }
      if ($this->is_queue_empty()) return $this->maybe_wp_die();
      check_ajax_referer($this->identifier, 'nonce');
      $this->handle();
      return $this->maybe_wp_die();
    }

    /**
     * Processes batches until completion or a time/memory/status limit is reached.
     *
     * The transient lock is advisory: workers must check is_processing() before
     * calling this method or the cron healthcheck.
     */
    protected function handle() {
      $this->lock_process();
      $throttle = max(0, (int) apply_filters($this->identifier . '_seconds_between_batches', apply_filters($this->prefix . '_seconds_between_batches', 0)));
      do {
        $batch = $this->get_batch();
        if (empty($batch) || empty($batch->data)) break;
        foreach ($batch->data as $key => $value) {
          $task = $this->task($value);
          if (false !== $task) $batch->data[$key] = $task;
          else unset($batch->data[$key]);
          if (!empty($batch->data)) $this->update($batch->key, $batch->data);
          if ($throttle) sleep($throttle);
          if ($this->time_exceeded() || $this->memory_exceeded() || $this->is_paused() || $this->is_cancelled()) break;
        }
        if (empty($batch->data)) $this->delete($batch->key);
      } while (!$this->time_exceeded() && !$this->memory_exceeded() && !$this->is_queue_empty() && !$this->is_paused() && !$this->is_cancelled());
      $this->unlock_process();
      if (!$this->is_queue_empty()) $this->dispatch();
      else $this->complete();
      return $this->maybe_wp_die();
    }

    /** @return \stdClass|null Next batch, or null when no batches remain. */
    protected function get_batch() {
      return array_reduce($this->get_batches(1), function ($carry, $batch) {
        return $batch;
      }, null);
    }

    /**
     * Loads batches directly from the options table used by this installation.
     *
     * @param int $limit Maximum batches to load; zero means all batches.
     * @return \stdClass[] Batch objects containing `key` and unserialized `data`.
     */
    public function get_batches($limit = 0) {
      global $wpdb;
      $table = $wpdb->options;
      $column = 'option_name';
      $key_column = 'option_id';
      $value_column = 'option_value';
      if (is_multisite()) {
        $table = $wpdb->sitemeta;
        $column = 'meta_key';
        $key_column = 'meta_id';
        $value_column = 'meta_value';
      }
      $key = $wpdb->esc_like($this->identifier . '_batch_') . '%';
      $sql = "SELECT * FROM {$table} WHERE {$column} LIKE %s ORDER BY {$key_column} ASC";
      $args = array($key);
      if (!empty($limit)) {
        $sql .= ' LIMIT %d';
        $args[] = (int) $limit;
      }
      $items = $wpdb->get_results($wpdb->prepare($sql, $args));
      $allowed = $this->allowed_batch_data_classes;
      return array_map(function ($item) use ($column, $value_column, $allowed) {
        $batch = new \stdClass();
        $batch->key = $item->{$column};
        $batch->data = self::maybe_unserialize($item->{$value_column}, $allowed);
        return $batch;
      }, $items ?: array());
    }

    /**
     * Unserializes batch data with an allowed-class restriction when configured.
     *
     * @param string $data Serialized or scalar option value.
     * @param bool|array $allowed_classes Allowed class names, or a boolean for all/none.
     * @return mixed Unserialized data.
     */
    protected static function maybe_unserialize($data, $allowed_classes) {
      if (!is_serialized($data)) return $data;
      $options = array('allowed_classes' => $allowed_classes);
      return @unserialize($data, $options);
    }

    /** @return bool True when no batch options remain. */
    protected function is_queue_empty() {
      return empty($this->get_batch());
    }

    /** @return bool True when at least one batch remains. */
    public function is_queued() {
      return !$this->is_queue_empty();
    }

    /** @return bool True when the queue's transient process lock is present. */
    public function is_processing() {
      return (bool) get_site_transient($this->identifier . '_process_lock');
    }

    /** @return bool True when queued, processing, paused, or cancelled. */
    public function is_active() {
      return $this->is_queued() || $this->is_processing() || $this->is_paused() || $this->is_cancelled();
    }

    /** Sets the transient process lock used to discourage concurrent workers. */
    protected function lock_process() {
      $this->start_time = time();
      $duration = property_exists($this, 'queue_lock_time') ? $this->queue_lock_time : 60;
      $duration = apply_filters($this->identifier . '_queue_lock_time', $duration);
      set_site_transient($this->identifier . '_process_lock', microtime(), $duration);
    }

    /**
     * Deletes the transient process lock.
     *
     * @return Background_Process This queue for chaining.
     */
    protected function unlock_process() {
      delete_site_transient($this->identifier . '_process_lock');
      return $this;
    }

    /**
     * Returns the PHP memory limit in bytes, using 32 GB for unlimited memory.
     *
     * @return int Memory limit in bytes.
     */
    protected function get_memory_limit() {
      $limit = function_exists('ini_get') ? ini_get('memory_limit') : '128M';
      if (!$limit || (int) $limit === -1) $limit = '32000M';
      return wp_convert_hr_to_bytes($limit);
    }

    /**
     * Preserves the legacy process-running helper used by subclasses.
     *
     * @return bool Whether the process lock is present.
     */
    protected function is_process_running() {
      return $this->is_processing();
    }

    /**
     * Checks whether memory usage reached 90% of the configured limit.
     *
     * @return bool True when another batch should not start in this worker.
     */
    protected function memory_exceeded() {
      $exceeded = memory_get_usage(true) >= $this->get_memory_limit() * 0.9;
      return apply_filters($this->identifier . '_memory_exceeded', $exceeded);
    }

    /**
     * Checks whether this worker exceeded its 20-second default time limit.
     *
     * @return bool True when another item should not start in this worker.
     */
    protected function time_exceeded() {
      $exceeded = time() >= $this->start_time + apply_filters($this->identifier . '_default_time_limit', 20);
      return apply_filters($this->identifier . '_time_exceeded', $exceeded);
    }

    /**
     * Generates a unique option name for a batch.
     *
     * @param int $length Maximum option-name length.
     * @param string $key Key type, normally `batch`.
     * @return string Option name.
     */
    protected function generate_key($length = 64, $key = 'batch') {
      return substr($this->identifier . '_' . $key . '_' . md5(microtime() . wp_rand()), 0, $length);
    }

    /** @return string Option name used for paused/cancelled status. */
    protected function get_status_key() {
      return $this->identifier . '_status';
    }

    /** Marks the queue cancelled and dispatches its cleanup handler. */
    public function cancel() {
      update_site_option($this->get_status_key(), self::STATUS_CANCELLED);
      $this->dispatch();
    }

    /** Preserves the legacy cancel_process() entry point. */
    public function cancel_process() {
      $this->cancel();
    }

    /** @return bool True when the queue was cancelled. */
    public function is_cancelled() {
      return absint(get_site_option($this->get_status_key(), 0)) === self::STATUS_CANCELLED;
    }

    /** Fires the queue-specific cancellation hook. */
    protected function cancelled() {
      do_action($this->identifier . '_cancelled');
    }

    /** Marks the queue paused without deleting pending batches. */
    public function pause() {
      update_site_option($this->get_status_key(), self::STATUS_PAUSED);
    }

    /** @return bool True when the queue is paused. */
    public function is_paused() {
      return absint(get_site_option($this->get_status_key(), 0)) === self::STATUS_PAUSED;
    }

    /** Fires the queue-specific pause hook. */
    protected function paused() {
      do_action($this->identifier . '_paused');
    }

    /** Clears paused status, schedules recovery, and attempts loopback dispatch. */
    public function resume() {
      delete_site_option($this->get_status_key());
      $this->schedule_event();
      $this->dispatch();
      $this->resumed();
    }

    /** Fires the queue-specific resume hook. */
    protected function resumed() {
      do_action($this->identifier . '_resumed');
    }

    /** Clears status and the recovery event, then runs the completion hook. */
    protected function complete() {
      delete_site_option($this->get_status_key());
      $this->clear_scheduled_event();
      $this->completed();
    }

    /** Fires the queue-specific completion hook. */
    protected function completed() {
      do_action($this->identifier . '_completed');
    }

    /**
     * Returns the recovery interval in minutes, defaulting to five.
     *
     * @return int Positive interval in minutes.
     */
    public function get_cron_interval() {
      $interval = property_exists($this, 'cron_interval') ? $this->cron_interval : 5;
      $interval = apply_filters($this->cron_interval_identifier, $interval);
      return is_int($interval) && $interval > 0 ? $interval : 5;
    }

    /**
     * Registers this queue's custom cron schedule.
     *
     * @param array $schedules Existing WordPress cron schedules.
     * @return array Schedules with this queue's recovery interval added.
     */
    public function schedule_cron_healthcheck($schedules) {
      $interval = $this->get_cron_interval();
      $schedules[$this->cron_interval_identifier] = array(
        'interval' => MINUTE_IN_SECONDS * $interval,
        'display' => $interval === 1 ? __('Every Minute') : sprintf(__('Every %d Minutes'), $interval),
      );
      return $schedules;
    }

    /** Schedules the recurring recovery event if it is not already present. */
    protected function schedule_event() {
      if (!wp_next_scheduled($this->cron_hook_identifier)) {
        wp_schedule_event(time() + $this->get_cron_interval() * MINUTE_IN_SECONDS, $this->cron_interval_identifier, $this->cron_hook_identifier);
      }
    }

    /** Removes the next scheduled recovery event, if present. */
    protected function clear_scheduled_event() {
      $timestamp = wp_next_scheduled($this->cron_hook_identifier);
      if ($timestamp) wp_unschedule_event($timestamp, $this->cron_hook_identifier);
    }

    /**
     * Drains a queued batch directly from cron without requiring loopback HTTP.
     *
     * Active workers are left alone, and empty queues have their recovery event cleared.
     */
    public function handle_cron_healthcheck() {
      if ($this->is_processing()) return;
      if ($this->is_queue_empty()) {
        $this->clear_scheduled_event();
        return;
      }
      $this->handle();
    }

    /**
     * Preserves the legacy worker termination filter for CLI and test callers.
     *
     * @param mixed $return Value returned when termination is disabled by filter.
     * @return mixed The supplied return value, or the result of wp_die().
     */
    protected function maybe_wp_die($return = null) {
      if (apply_filters($this->identifier . '_wp_die', true)) wp_die();
      return $return;
    }

    /**
     * Processes one queue item.
     *
     * @param mixed $item Task payload supplied when the item was queued.
     * @return mixed False to remove the item, or a replacement payload to keep it queued.
     */
    abstract protected function task($item);
  }
}
