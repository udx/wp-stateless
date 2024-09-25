<?php
/**
 * Batch task manager
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Batch;

// Require lib classes if not yet available
if (!class_exists('UDX_WP_Async_Request')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-async-request.php', 'dir');
}

if (!class_exists('UDX_WP_Background_Process')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-background-process.php', 'dir');
}

use wpCloud\StatelessMedia\Helper;
use wpCloud\StatelessMedia\Singleton;

class BatchTaskManager extends \UDX_WP_Background_Process {
  use Singleton;
  
  const STATE_KEY = '_state';
  const UPDATED_KEY = '_updated';
  const HEALTH_CHECK_INTERVAL = 60 * 5; // 5 minute

  protected $prefix = 'sm';
  protected $action = 'batch_process';

  protected function __construct() {
    parent::__construct();

    $this->_init_hooks();
    $this->_check_force_continue();
  }

  private function _init_hooks() {
    add_filter('wp_stateless_batch_state', [$this, 'get_state'], 10, 1);
    add_filter('wp_stateless_batch_action_pause', [$this, 'pause_task'], 10, 2);
    add_filter('wp_stateless_batch_action_resume', [$this, 'resume_task'], 10, 2);
    add_filter('heartbeat_send', [$this, 'check_running_batch'], 10, 1 );
  }

  /**
   * Check if we should force dispatch
   * Check if the task is in progress and if the state was not updated last 5 minutes - try to continue the task
   */
  private function _check_force_continue() {
    $last_updated = $this->_get_last_updated();

    if ( empty($last_updated) || $this->is_paused() ) {
      return;
    }

    $check_interval = self::HEALTH_CHECK_INTERVAL;

    if ( defined('WP_STATELESS_BATCH_HEALTHCHECK_INTERVAL') ) {
      $check_interval = max($check_interval, WP_STATELESS_BATCH_HEALTHCHECK_INTERVAL * 60);
    }

    if ( time() - $last_updated <= $check_interval ) {
      return;
    }

    Helper::log('Batch task freezed, trying to continue...');

    // Forcing continue
    $this->unlock_process();
    $this->handle();
  }

  /**
   * Update current task state
   * 
   * @return array
   */
  private function _update_state($state) {
    update_option( $this->identifier . self::STATE_KEY, $state );
    update_option( $this->identifier . self::UPDATED_KEY, time() );
  }

  /**
   * Get current task state
   * 
   * @return array
   */
  private function _get_state() {
    // We need to omit the cache and get the data directly from the db
    global $wpdb;

    $sql = "SELECT option_value FROM $wpdb->options WHERE option_name = '%s' LIMIT 1";
    $sql = $wpdb->prepare($sql, $this->identifier . self::STATE_KEY);
    $state = $wpdb->get_var($sql);

    return empty($state) ? [] : maybe_unserialize($state);
  }

  /**
   * Get last state update of the current task
   * 
   * @return int|null
   */
  private function _get_last_updated() {
    // We need to omit the cache and get the data directly from the db
    global $wpdb;

    $sql = "SELECT option_value FROM $wpdb->options WHERE option_name = '%s' LIMIT 1";
    $sql = $wpdb->prepare($sql, $this->identifier . self::UPDATED_KEY);

    return $wpdb->get_var($sql);
  }

  /**
   * Delete current task state
   * 
   * @return array
   */
  private function _delete_state() {
    delete_option( $this->identifier . self::STATE_KEY );
    delete_option( $this->identifier . self::UPDATED_KEY );
  }

  /**
   * Add new batch to the queue
   * 
   * @param array $batch
   */
  private function _add_batch($batch) {
    if ( !empty($batch) ) {
      $this->data( $batch )->save();
    }
  }

  /**
   * Get task object
   * 
   * @param string $state|null
   * @return IBatchTask
   * @throws \Exception
   */
  private function _get_batch_task_object($state = null) {
    if ( empty($state) ) {
      $state = $this->_get_state();
    }

    if ( !isset($state['class']) || !isset($state['file']) ) {
      throw new \Exception("Can not get batch task file and class");
    }

    $class = $state['class'];

    if ( !class_exists($class) ) {
      require_once $state['file'];
    }

    $object = new $class();

    if ( !is_a($object, '\wpCloud\StatelessMedia\Batch\IBatchTask') ) {
      throw new \Exception("Batch task $class is not valid");
    }

    $object->set_state($state);

    return $object;
  }

  /**
   * Start the batch task
   *
   * @param string $class
   * @param string|null $file
   * @param string $email
   */
  public function start_task($class, $file = null, $email = '', $queue = []) {
    try {
      // Prepare default state
      $defaults = [
        'class'       => $class,
        'file'        => $file,
        'email'       => $email,
        'queue'       => $queue,
      ];

      $task_object = $this->_get_batch_task_object($defaults);
      $task_object->init_state();
      
      // Batch should be run prior to 'get_state' because it mutates the state
      $this->_add_batch( $task_object->get_batch() );

      // Save state
      $state = wp_parse_args($task_object->get_state(), $defaults);
      
      $this->_update_state( $state );

      Helper::log('Batch task started: ' . $class);

      do_action('wp_stateless_batch_task_started', $class, $file);
    } catch (\Throwable $e) {
      Helper::log("Batch task $class failed to start: " . $e->getMessage());
      
      do_action('wp_stateless_batch_task_failed', $class, $file, $e->getMessage());

      return;
    }

    $this->dispatch();
  }

  /**
   * Process batch task item.
   * Returns false to remove item from queue
   * Returns $item to repeat 
   * 
   * @param string $item
   * @return bool|mixed
   */
  public function task($item) {
    $result = false;

    try {
      $object = $this->_get_batch_task_object();

      $result = $object->process_item($item);
      $this->_update_state( $object->get_state() );

      $result = apply_filters('wp_stateless_batch_task_item_processed', $result, $item);
    } catch (\Throwable $e) {
      Helper::log( "Batch task unable to handle item $item: " . $e->getMessage() );
      
      $result = apply_filters('wp_stateless_batch_task_item_failed', $result, $item);
    }

    return $result;
  }
  
  /**
   * Complete the batch task. Tries to get the next batch and continue
   */
  protected function complete() {
    $class = '';

    // Check if we have more batched to run
    try {
      $object = $this->_get_batch_task_object();
      $class = get_class($object);
      $batch = $object->get_batch();

      if ( !empty($batch) ) {
        $this->_add_batch( $batch );
        $this->_update_state( $object->get_state() );

        $this->dispatch();

        return;
      }

      Helper::log( 'Batch task completed: ' . $class );
    } catch (\Throwable $e) {
      Helper::log( "Unable to process next batch: " . $e->getMessage() );
    }

    // If no more batches - delete state
    $state = $this->_get_state();

    parent::complete();
    $this->_delete_state();

    do_action('wp_stateless_batch_task_finished', $class, $state);

    $site = site_url();
    $subject = sprintf( __('WP-Stateless: Data Optimization Complete', ud_get_stateless_media()->domain) );
    $message = sprintf(
      __("WP-Stateless data has been optimized for %s.\n\nIf you have WP_STATELESS_SYNC_LOG or WP_DEBUG_LOG enabled, review those logs now to review any errors that may have occurred during the synchronization process.", ud_get_stateless_media()->domain), 
      $site
    );

    do_action('wp_stateless_send_admin_email', $subject, $message, $state['email'] ?? '');
  }

  /**
   * Check if batch task has a state, so it is in progress
   * Because is_processing is true only while processing an item
   * 
   * @param array|null $state
   * @return bool
   */
  public function is_running($state = null) {
    if ( empty($state) ) {
      $state = $this->_get_state();
    }

    return !empty($state);
  }

  /**
   * Get the state of the current batch process
   * 
   * @param mixed $status
   * @return mixed
   */
  public function get_state($state) {
    $state = $this->_get_state();

    unset($state['class']);
    unset($state['file']);

    $state['is_running'] = $this->is_running($state);
    $state['is_paused'] = $this->is_paused();

    return $state;
  }

  /**
   * Pause the batch task
   * 
   * @param array $state
   * @param array $params
   * @return array
   */
  public function pause_task($state, $params) {
    $this->pause();

    return apply_filters('wp_stateless_batch_state', $state, []);
  }

  /**
   * Resume the batch task
   * 
   * @param array $state
   * @param array $params
   * @return array
   */
  public function resume_task($state, $params) {
    $this->resume();

    return apply_filters('wp_stateless_batch_state', $state, []);
  }

  /**
   * Get the state key
   * 
   * @return string
   */
  public function get_state_key() {
    return $this->identifier . self::STATE_KEY;
  }

  /**
   * Check if batch is running during WP heartbeat request
   * 
   * @return array
   */
  public function check_running_batch($response) {
    if ( $this->is_running() ) {
      $response['stateless-batch-running'] = true;
    }

    return $response;
  }
}
