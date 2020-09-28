<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Sync\BackgroundSync;
use wpCloud\StatelessMedia\Utility;

class FileSync extends BackgroundSync {

  /**
   * Make is singleton
   */
  use Singleton;

  /**
   * Unique action
   */
  protected $action = 'wps_bg_file_sync';

  /**
   * Allow sorting for this kind of sync
   */
  protected $allow_sorting = true;

  /**
   * Allow setting the limit for this kind of sync
   */
  protected $allow_limit = true;

  /**
   * Name
   */
  public function get_name() {
    return __('Media Library Files', ud_get_stateless_media()->domain);
  }

  /**
   * Helper window
   */
  public function get_helper_window() {
    return new HelperWindow(
      __('What are Media Library Files?', ud_get_stateless_media()->domain),
      __('All non-image files that were uploaded via the media library or via plugins that use standard uploading API.', ud_get_stateless_media()->domain),
    );
  }

  /**
   * Start process
   */
  public function start($args = []) {
    if ($this->is_process_running()) return false;

    $this->log("Start");

    // Make sure there is no orphaned data and state
    delete_site_option("{$this->action}_stopped");
    $this->cancel_process();
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
              AND post_mime_type NOT LIKE 'image/%'
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
    return true;
  }

  /**
   * 
   */
  public function task($id) {
    try {
      if ($this->is_stopped()) return false;
      timer_start();

      @error_reporting(0);

      if (ud_get_stateless_media()->is_connected_to_gs() !== true) {
        throw new FatalException(__('Not connected to GCS', ud_get_stateless_media()->domain));
      }

      $file = get_post($id);

      if (!$file || 'attachment' != $file->post_type) {
        throw new UnprocessableException(sprintf(__('Attachment not found: %s is an invalid file ID.', ud_get_stateless_media()->domain), $id));
      }

      $fullsizepath = get_attached_file($file->ID);
      $local_file_exists = file_exists($fullsizepath);

      if (false === $fullsizepath || !$local_file_exists) {

        // Try get it and save
        $result_code = ud_get_stateless_media()->get_client()->get_media(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", "", true), true, $fullsizepath);

        if ($result_code !== 200) {
          if (!Utility::sync_get_attachment_if_exist($file->ID, $fullsizepath)) { // Save file to local from proxy.
            throw new UnprocessableException(sprintf(__('File not found (%s)', ud_get_stateless_media()->domain), $file->guid));
          } else {
            $local_file_exists = true;
          }
        } else {
          $local_file_exists = true;
        }
      }

      if ($local_file_exists) {

        if (!ud_get_stateless_media()->get_client()->media_exists(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", "", true))) {

          @set_time_limit(-1);
          if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . '/wp-admin/includes/image.php';
          }
          $metadata = wp_generate_attachment_metadata($file->ID, $fullsizepath);

          if (is_wp_error($metadata)) {
            throw new UnprocessableException($metadata->get_error_message());
          }

          wp_update_attachment_metadata($file->ID, $metadata);
          do_action('sm:synced::nonImage', $id, $metadata);
        } else {
          // Ephemeral and Stateless modes: we don't need the local version.
          if (ud_get_stateless_media()->get('sm.mode') === 'ephemeral' || ud_get_stateless_media()->get('sm.mode') === 'stateless') {
            unlink($fullsizepath);
          }
        }
      }

      $this->log(sprintf(__('%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($file->ID)), $file->ID, timer_stop()));

      $processedCount = intval($this->get_process_meta('processed'));
      $this->save_process_meta([
        'processed' => ++$processedCount
      ]);

      if (!$this->is_stopped()) {
        $this->extend_queue();
      }

      return false;
    } catch (FatalException $e) {
      $this->log("Stopped due to error - {$e->getMessage()}");
      $this->stop();
      return false;
    } catch (UnprocessableException $e) {
      $this->log($e->getMessage());
      return false;
    } catch (\Throwable $e) {
      $this->log("Stopped due to error - {$e->getMessage()}");
      $this->stop();
      return false;
    }
  }

  public function extend_queue() {
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
              AND post_mime_type NOT LIKE 'image/%'
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
  }

  /**
   * 
   */
  protected function complete() {
    parent::complete();

    // @todo do something when complete
    $this->log("Complete");
  }

  /**
   * 
   */
  public function get_total_items() {
    $cached = get_transient($transKey = "{$this->action}_total_items");
    if ($cached) return intval($cached);

    global $wpdb;
    $sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%'";
    $total = $wpdb->get_var($sql);

    set_transient($transKey, $total, MINUTE_IN_SECONDS * 5);
    return intval($total);
  }
}
