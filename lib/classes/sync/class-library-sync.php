<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\UnprocessableException;

abstract class LibrarySync extends BackgroundSync {

  /**
   * Condition SQL
   * Should be defined in child classes
   */
  abstract public function get_sql_condition();

  /**
   * Get transient key for total items value
   */
  private function get_total_items_trans_key() {
    return "{$this->action}_total_items";
  }

  /**
   * Start the process
   * 
   * @param array $args []
   * @return bool
   */
  public final function start($args = []) {
    try {
      if ($this->is_process_running()) throw new UnprocessableException(__('Process already running', ud_get_stateless_media()->domain));

      // Make sure there is no orphaned data and state
      delete_site_option($this->get_stopped_option_key());
      delete_transient($this->get_total_items_trans_key());
      $this->clear_process_meta();

      $settings = wp_parse_args($args, [
        'limit' => null,
        'order' => null
      ]);

      $limit = $settings['limit'] ? intval($settings['limit']) : 0;
      $order = in_array($settings['order'], ['desc', 'asc']) ? $settings['order'] : 'desc';

      global $wpdb;
      $sql = "SELECT ID FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
              {$this->get_sql_condition()}
              AND post_date < %s 
            ORDER BY ID $order 
            LIMIT %d";
      $query = $wpdb->prepare($sql, $datetime = current_time('mysql'), $this->get_max_batch_size());
      $ids = $wpdb->get_col($query);

      $total = 0;
      foreach ($ids as $id) {
        if (!$limit || $total < $limit) {
          $this->push_to_queue($id);
          $total++;
        }
      }

      $this->save_process_meta([
        'limit' => $limit,
        'datetime' => $datetime,
        'order' => $order,
        'last_id' => $id
      ]);

      $this->save()->dispatch();
      $this->log('Started');
      return true;
    } catch (UnprocessableException $e) {
      $this->log(sprintf(__('Could not start the new process: %s'), $e->getMessage()));
      return true;
    } catch (\Throwable $e) {
      $this->log(sprintf(__('Could not start the process due to the error: %s'), $e->getMessage()));
      $this->stop();
      return false;
    }
  }

  /**
   * Provide the queue with the new data if available
   * 
   * @return bool
   */
  public function extend_queue() {
    try {
      global $wpdb;

      $meta = $this->get_process_meta();
      $last_id = isset($meta['last_id']) ? $meta['last_id'] : false;

      if (!$last_id) return;

      $limit = isset($meta['limit']) ? $meta['limit'] : 0;
      $order = isset($meta['order']) ? $meta['order'] : 'desc';
      $datetime = isset($meta['datetime']) ? $meta['datetime'] : current_time('mysql');

      $range_condition = $order === 'desc' ? $wpdb->prepare("AND ID < %d", $last_id) : $wpdb->prepare("AND ID > %d", $last_id);

      $sql = "SELECT ID FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
              {$this->get_sql_condition()}
              AND post_date < %s 
              $range_condition
            ORDER BY ID $order 
            LIMIT %d";
      $query = $wpdb->prepare($sql, $datetime, $this->get_max_batch_size());
      $ids = $wpdb->get_col($query);

      $total = $this->get_queue_size();
      foreach ($ids as $id) {
        if (!$limit || $total < $limit) {
          $this->push_to_queue($id);
          $total++;
        }
      }

      if (!empty($this->data)) {
        $this->save()->save_process_meta([
          'last_id' => $id
        ]);
      } else {
        $this->save_process_meta([
          'last_id' => 0
        ]);
      }

      return true;
    } catch (\Throwable $e) {
      $this->log(sprintf('Something went wrong while extending the queue: %s. Stopping the whole process.', $e->getMessage()));
      $this->stop();
      return false;
    }
  }

  /**
   * Thing to do in complete
   */
  protected function complete() {
    parent::complete();

    // @todo do something when complete
    $this->log("Complete");
  }

  /**
   * Get Total Items (caching utilized)
   * 
   * @return int
   */
  public function get_total_items() {
    $cached = get_transient($this->get_total_items_trans_key());
    if ($cached) return intval($cached);

    global $wpdb;
    $sql = "SELECT count(*) 
      FROM $wpdb->posts 
      WHERE post_type = 'attachment' 
        {$this->get_sql_condition()}";
    $total = $wpdb->get_var($sql);

    set_transient($this->get_total_items_trans_key(), $total, MINUTE_IN_SECONDS * 5);
    return intval($total);
  }

  /**
   * Notice if process seemed to be stuck
   * 
   * @return string|false
   */
  public function get_process_notice() {
    $notices = parent::get_process_notice();
    $last = intval($this->get_process_meta('last_at'));
    if (!$last) {
      $last = strtotime($this->get_process_meta('datetime'));
      if (false === $last) return $notices;
    }

    if (!property_exists($this, 'cron_interval')) return $notices;

    $waiting = current_time('timestamp') - $last;
    if ($waiting < 5 * MINUTE_IN_SECONDS * $this->cron_interval) return $notices;

    $notices[] = sprintf(__('This process takes longer than it should. Please, make sure loopback connections and WP Cron are enabled and working, or try restarting the process.', ud_get_stateless_media()->domain));
    return $notices;
  }
}
