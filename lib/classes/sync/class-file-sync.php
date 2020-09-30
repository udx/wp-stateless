<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Utility;

class FileSync extends LibrarySync {

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
   * Get SQL condition to compose the query to get items to process by library sync
   * 
   * @return string
   */
  public function get_sql_condition() {
    return "AND post_mime_type NOT LIKE 'image/%'";
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
   * Process one file item
   * 
   * @param mixed $id
   * @return bool
   */
  protected function task($id) {
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
        $result_code = ud_get_stateless_media()->get_client()->get_media(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", ""), true, $fullsizepath);

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

        if (!ud_get_stateless_media()->get_client()->media_exists(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", ""))) {

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

      if (!$this->is_stopped()) {
        $this->extend_queue();
      }

      parent::task($id);
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
}
