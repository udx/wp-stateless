<?php

/**
 * CLI Sync Implementation
 * 
 * @author palant@UDX
 * @author korotkov@UDX
 */

use wpCloud\StatelessMedia\FatalException;
use wpCloud\StatelessMedia\UnprocessableException;
use wpCloud\StatelessMedia\Utility;

if (!class_exists('SM_CLI_Scaffold')) {
  require_once(dirname(__FILE__) . '/class-sm-cli-scaffold.php');
}

class SM_CLI_Sync extends SM_CLI_Scaffold {

  /**
   * Order
   */
  public $order = "";

  /**
   * Start
   */
  public $start = false;

  /**
   * End
   */
  public $end = false;

  /**
   * Limit
   */
  public $limit = false;

  /**
   * Batch
   */
  public $batch = false;

  /**
   * Batches
   */
  public $batches = false;

  /**
   * Total
   */
  public $total = 0;

  /**
   * Log
   */
  public $log;

  /**
   * Sync images
   */
  public function images() {
    global $wpdb;

    /** Prepare arguments */
    $this->_prepare();

    $timer = time();

    /** Get Total Amount of Attachments */
    $this->total = $wpdb->get_var("
      SELECT
      COUNT(ID)
      FROM {$wpdb->posts}
      WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
    ");

    if ($this->batch) {
      $this->output("Running Batch {$this->batch} from {$this->batches}");
      $range = round($this->total / $this->batches);
      $this->start = ($this->batch * $range) - $range;
      $this->end = $this->batches == $this->batch ? $this->total : $this->batch * $range;
      $this->output("Starting from {$this->start} row. ");
      $this->output("And proceeding up to {$this->end} row.");
    } else {
      $this->output("Running in default way. Starting from {$this->start} row and proceeding up to end.");
      $this->end = $this->end ? $this->end : $this->total;
    }
    $media_to_proceed = $this->end - $this->start;

    //** Counters */
    $synced_images = 0;

    WP_CLI::line('Starting extract attachments.');

    @error_reporting(0);
    @set_time_limit(-1);

    for ($this->start; $this->start < $this->end; $this->start += $this->limit) {

      $limit = ($this->end - $this->start) < $this->limit ? ($this->end - $this->start) : $this->limit;

      $this->output('Synced: ' . $synced_images . '. Extracting from ' . ($this->start + 1) . ' to ' . ($this->start + $limit));

      /**
       * Get Attachments data.
       */
      $attachments = $wpdb->get_results($wpdb->prepare("
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID {$this->order}
        LIMIT %d, %d;
      ", $this->start, $limit), ARRAY_A);

      if (!empty($attachments)) {
        foreach ($attachments as $a) {
          try {
            timer_start();
            $response = Utility::process_image_by_id(intval($a['ID']));
            if (!empty($response)) {
              $synced_images++;
              $this->output(sprintf(__('%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($response->ID)), $response->ID, timer_stop()));
            }
          } catch (FatalException $e) {
            $this->output($e->getMessage());
            break;
          } catch (UnprocessableException $e) {
            $this->output($e->getMessage());
          } catch (\Throwable $e) {
            $this->output($e->getMessage());
            break;
          }

          /** Flush data */
          $wpdb->flush();
          @ob_flush();
          @flush();
        }

        unset($attachments);
      }
    }
    WP_CLI::success("Stateless Media is synced");
    WP_CLI::line('Media which have been checked: ' . number_format_i18n($media_to_proceed));
    WP_CLI::line('Synced stateless for ' . number_format_i18n($synced_images) . ' attachments');
    WP_CLI::line('Spent Time: ' . (time() - $timer) . ' sec');
  }


  /**
   * Sync files
   */
  public function files() {
    global $wpdb;

    /** Prepare arguments */
    $this->_prepare();

    $timer = time();

    /** Get Total Amount of Attachments */
    $this->total = $wpdb->get_var("
      SELECT
      COUNT(ID)
      FROM {$wpdb->posts}
      WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%'
    ");

    if ($this->batch) {
      $this->output("Running Batch {$this->batch} from {$this->batches}");
      $range = round($this->total / $this->batches);
      $this->start = ($this->batch * $range) - $range;
      $this->end = $this->batches == $this->batch ? $this->total : $this->batch * $range;
      $this->output("Starting from {$this->start} row. ");
      $this->output("And proceeding up to {$this->end} row.");
    } else {
      $this->output("Running in default way. Starting from {$this->start} row and proceeding up to end.");
      $this->end = $this->end ? $this->end : $this->total;
    }
    $media_to_proceed = $this->end - $this->start;

    //** Counters */
    $synced_files = 0;

    WP_CLI::line('Starting extract attachments.');

    @error_reporting(0);
    @set_time_limit(-1);

    for ($this->start; $this->start < $this->end; $this->start += $this->limit) {

      $limit = ($this->end - $this->start) < $this->limit ? ($this->end - $this->start) : $this->limit;

      $this->output('Synced: ' . $synced_files . '. Extracting from ' . ($this->start + 1) . ' to ' . ($this->start + $limit));

      /**
       * Get Attachments data.
       */
      $attachments = $wpdb->get_results($wpdb->prepare("
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%' ORDER BY ID {$this->order}
        LIMIT %d, %d;
      ", $this->start, $limit), ARRAY_A);

      if (!empty($attachments)) {
        foreach ($attachments as $a) {
          try {
            timer_start();
            $response = Utility::process_file_by_id(intval($a['ID']));
            if (!empty($response)) {
              $synced_files++;
              $this->output(sprintf(__('%1$s (ID %2$s) was successfully synchronised in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($response->ID)), $response->ID, timer_stop()));
            }
          } catch (FatalException $e) {
            $this->output($e->getMessage());
            break;
          } catch (UnprocessableException $e) {
            $this->output($e->getMessage());
          } catch (\Throwable $e) {
            $this->output($e->getMessage());
            break;
          }

          /** Flush data */
          $wpdb->flush();
          @ob_flush();
          @flush();
        }

        unset($attachments);
      }
    }
    WP_CLI::success("Stateless Media is synced");
    WP_CLI::line('Media which have been checked: ' . number_format_i18n($media_to_proceed));
    WP_CLI::line('Synced stateless for ' . number_format_i18n($synced_files) . ' attachments');
    WP_CLI::line('Spent Time: ' . (time() - $timer) . ' sec');
  }

  /**
   * Prepare
   */
  private function _prepare() {
    $args = $this->assoc_args;
    if (isset($args['b'])) {
      WP_CLI::error('Invalid parameter --b. Command must not be run directly with --b parameter.');
    }
    $this->start          = isset($args['start']) && is_numeric($args['start']) ? $args['start'] : 0;
    $this->limit          = isset($args['limit']) && is_numeric($args['limit']) ? $args['limit'] : 100;
    $this->force          = isset($args['force'])         ? true : false;
    $this->continue       = isset($args['continue'])      ? true : false;
    $this->fix            = isset($args['fix'])           ? true : false;
    $this->order          = isset($args['order']) && strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
    if (isset($args['batch'])) {
      if (!is_numeric($args['batch']) || $args['batch'] <= 0) {
        WP_CLI::error('Invalid parameter --batch');
      }
      $this->batch = $args['batch'];
      $this->batches = isset($args['batches']) ? $args['batches'] : 10;
      if (!is_numeric($this->batches) || $this->batches <= 0) {
        WP_CLI::error('Invalid parameter --batches');
      } elseif ($this->batch > $this->batches) {
        WP_CLI::error('--batch parameter must is invalid. It must not equal or less then --batches');
      }
    } else {
      $this->end = isset($args['end']) && is_numeric($args['end']) ? $args['end'] : false;
    }
  }
}
