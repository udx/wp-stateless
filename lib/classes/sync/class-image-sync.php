<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\Singleton;

/**
 * 
 */
class ImageSync extends BackgroundSync {

  /**
   * Make is singleton
   */
  use Singleton;

  /**
   * Unique action
   */
  protected $action = 'wps_bg_image_sync';

  /**
   * Name
   */
  public function get_name() {
    return __('Media Library Images', ud_get_stateless_media()->domain);
  }

  /**
   * Helper window
   */
  public function get_helper_window() {
    return new HelperWindow(
      __('What are Media Library Objects?', ud_get_stateless_media()->domain),
      __('All images and other files that were uploaded via the media library or via plugins that use standard uploading API.', ud_get_stateless_media()->domain),
    );
  }

  /**
   * Total items
   */
  public function get_total_items() {
    $cached = get_transient($transKey = "{$this->action}_total_items");
    if ($cached) return intval($cached);

    global $wpdb;
    $sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'";
    $total = $wpdb->get_var($sql);

    set_transient($transKey, $total, MINUTE_IN_SECONDS * 5);
    return intval($total);
  }

  /**
   * Process 1 item from the queue
   */
  protected function task($item) {
    sleep(5);
    error_log("ImageSync Item Processed");
    error_log(print_r($item, true));
    return false;
  }

  /**
   * Extending save method
   */
  public function save($limit = 0, $start_datetime = false) {
    // Remember limit for future requests
    if (is_int($limit) && $limit > 0) {
      update_site_option("{$this->action}_queue_limit", $limit);
    }

    // Remember start datetime to process only items created before this time
    if ($start_datetime && $timestamp = strtotime($start_datetime)) {
      update_site_option("{$this->action}_start_datetime", date('Y-m-d H:i:s', $timestamp));
    }

    return parent::save();
  }

  /**
   * Clear process meta
   */
  public function clear_process_meta() {
    // Clear limits for future starts
    delete_site_option("{$this->action}_queue_limit");

    // Unset date time of previous start
    delete_site_option("{$this->action}_start_datetime");
  }

  /**
   * Extending complete method
   */
  protected function complete() {
    parent::complete();

    $this->clear_process_meta();

    error_log("ImageSync Complete");
  }

  /**
   * Method to start processing the queue
   */
  public function start($args = []) {
    if ($this->is_process_running()) return false;

    // Make sure there is no orphaned data and state
    $this->cancel_process();
    $this->clear_process_meta();

    $settings = wp_parse_args($args, [
      'limit' => null,
      'order' => null
    ]);

    $limit = $settings['limit'] ? intval($settings['limit']) : 0;
    $order = in_array($settings['order'], ['desc', 'asc']) ? $settings['order'] : 'desc';

    $max_batch_size = (defined('WP_STATELESS_SYNC_MAX_BATCH_SIZE') && is_int(WP_STATELESS_SYNC_MAX_BATCH_SIZE)) ? WP_STATELESS_SYNC_MAX_BATCH_SIZE : 10;

    global $wpdb;
    $sql = "SELECT ID FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
              AND post_mime_type LIKE 'image/%'
              AND post_date < %s 
            ORDER BY ID $order 
            LIMIT %d";
    $query = $wpdb->prepare($sql, $datetime = current_time('mysql'), $max_batch_size);
    $ids = $wpdb->get_col($query);

    $total = 0;
    foreach ($ids as $id) {
      if (!$limit || $total < $limit) {
        $this->push_to_queue($id);
        $total++;
      }
    }

    $this->save($limit, $datetime)->dispatch();
  }

  /**
   * 
   */
  public function extend_queue() {
  }
}
