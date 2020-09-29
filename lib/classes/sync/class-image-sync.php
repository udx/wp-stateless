<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Utility;

/**
 * Background process for synchronization of media library images
 */
class ImageSync extends LibrarySync {

  /**
   * Make is singleton
   */
  use Singleton;

  /**
   * Unique action
   */
  protected $action = 'wps_bg_image_sync';

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
   * 
   * @return string
   */
  public function get_name() {
    return __('Media Library Images', ud_get_stateless_media()->domain);
  }

  /**
   * Get SQL condition to compose the query to get items to process by library sync
   * 
   * @return string
   */
  public function get_sql_condition() {
    return "AND post_mime_type LIKE 'image/%'";
  }

  /**
   * Helper window
   * 
   * @return HelperWindow
   */
  public function get_helper_window() {
    return new HelperWindow(
      __('What are Media Library Images?', ud_get_stateless_media()->domain),
      __('All image files that were uploaded via the media library or via plugins that use standard uploading API.', ud_get_stateless_media()->domain),
    );
  }

  /**
   * Process 1 item from the queue
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

      $image = get_post($id);

      if (!$image || 'attachment' != $image->post_type || 'image/' != substr($image->post_mime_type, 0, 6))
        throw new UnprocessableException(sprintf(__('Failed to process item: %s is an invalid image ID.', ud_get_stateless_media()->domain), $id));

      $fullsizepath = get_attached_file($image->ID);

      // If no file found
      if (false === $fullsizepath || !file_exists($fullsizepath)) {

        // Try get it and save
        $result_code = ud_get_stateless_media()->get_client()->get_media(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", "", true), true, $fullsizepath);

        if ($result_code !== 200) {
          if (!Utility::sync_get_attachment_if_exist($image->ID, $fullsizepath)) {
            throw new UnprocessableException(sprintf(__('Both local and remote files are missing. Unable to process. (%s)', ud_get_stateless_media()->domain), $image->guid));
          }
        }
      }

      @set_time_limit(-1);

      do_action('sm:pre::synced::image', $id);
      if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . '/wp-admin/includes/image.php';
      }
      $metadata = wp_generate_attachment_metadata($image->ID, $fullsizepath);

      if (get_post_mime_type($image->ID) !== 'image/svg+xml') {
        if (is_wp_error($metadata)) {
          throw new UnprocessableException($metadata->get_error_message());
        }

        if (empty($metadata)) {
          throw new UnprocessableException(sprintf(__('No metadata generated for %1$s (ID %2$s).', ud_get_stateless_media()->domain), esc_html(get_the_title($image->ID)), $image->ID));
        }
      }

      // trigger processing filters
      wp_update_attachment_metadata($image->ID, $metadata);
      do_action('sm:synced::image', $id, $metadata);

      // Ephemeral and Stateless modes: we don't need the local version.
      if (ud_get_stateless_media()->get('sm.mode') === 'ephemeral' || ud_get_stateless_media()->get('sm.mode') === 'stateless') {
        unlink($fullsizepath);
      }

      $this->log(sprintf(__('%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($image->ID)), $image->ID, timer_stop()));

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
