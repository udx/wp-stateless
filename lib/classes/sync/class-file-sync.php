<?php

namespace wpCloud\StatelessMedia\Sync;

use wpCloud\StatelessMedia\FatalException;
use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\UnprocessableException;
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
      __('All non-image files that were uploaded via the media library or via plugins that use standard uploading API.', ud_get_stateless_media()->domain)
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
      parent::before_task($id);

      timer_start();
      @error_reporting(0);
      @set_time_limit(-1);

      $file = Utility::process_file_by_id($id);
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
