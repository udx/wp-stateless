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
   * 
   */
  protected function task($item) {
    sleep(5);
    error_log("ImageSync Item Processed");
    error_log(print_r($item, true));
    return false;
  }

  /**
   * 
   */
  protected function complete() {
    parent::complete();

    error_log("ImageSync Complete");
  }

  /**
   * 
   */
  public function start($args = []) {
    if ($this->is_process_running()) return false;
    $this->cancel_process();

    $settings = wp_parse_args($args, [
      'limit' => null,
      'order' => null
    ]);

    $limit = $settings['limit'] ? intval($settings['limit']) : 0;
    $order = in_array($settings['order'], ['desc', 'asc']) ? $settings['order'] : 'desc';

    $max_batch_size = (defined('WP_STATELESS_SYNC_MAX_BATCH_SIZE') && is_int(WP_STATELESS_SYNC_MAX_BATCH_SIZE)) ? WP_STATELESS_SYNC_MAX_BATCH_SIZE : 10;

    global $wpdb;
    $sql = "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID $order LIMIT %d";
    $query = $wpdb->prepare($sql, $max_batch_size);
    $ids = $wpdb->get_col($query);

    print_r($ids);

    $total = 0;
    foreach ($ids as $id) {
      if (!$limit || $total < $limit) {
        $this->push_to_queue($id);
        $total++;
      }
    }

    $this->save()->dispatch();
  }

  /**
   * 
   */
  public function extend_queue() {
  }
}
