<?php
/**
 * Batch Task 
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Batch;

abstract class BatchTask implements IBatchTask {
  /**
   * Current state of the task
   * 
   * @var array
   */
  protected $state = [];

  /**
   * ID of the task
   * 
   * @var string
   */
  protected $id = 'batch_task';

  /**
   * Description of the task (for displaying status)
   * 
   * @var string
   */
  protected $description = '';

  /**
   * Total number of items to process. Default -1 - infinite/unknown
   * 
   * @var int
   */
  protected $total = -1;

  /**
   * Number of items processed
   * 
   * @var int
   */
  protected $completed = 0;

  /**
   * Number of items to process in a single batch
   * 
   * @var int
   */
  protected $limit = 20;

  /**
   * Offset for the next batch (database offset, page token, etc.)
   * 
   * @var mixed
   */
  protected $offset = 0;

  /**
   * Date and time when processing started
   * 
   * @var string|null
   */
  protected $started = null;

  /**
   * Initialize the state of the task
   * 
   * @param mixed $item
   */
  public function init_state() {
    $this->started = time();
  }

  /**
   * Get human-friendly description
   * 
   * @return string 
   */
  public function get_description() {
    return $this->description;
  }

  /**
   * Get task state
   * 
   * @return array 
   */
  public function get_state() {
    if ( empty($this->started) ) {
      $this->init_state();
    }

    // Calling process can add extra data to the state, so we should try to keep it
    return wp_parse_args([
      'id'          => $this->id,
      'description' => $this->description,
      'total'       => $this->total,
      'completed'   => $this->completed,
      'limit'       => $this->limit,
      'offset'      => $this->offset,
      'started'     => $this->started,
    ], $this->state);
  }

  /**
   * Restore task state for processing between calls
   * 
   * @param array $state
   * @return array 
   */
  public function set_state($state) {
    // Calling process can add extra data to the state, so we should try to keep it
    $this->state = $state;

    // Restore state properties required for processing 
    if ( isset($state['description']) ) {
      $this->description = $state['description'];
    }

    if ( isset($state['started']) ) {
      $this->started = $state['started'];
    }

    if ( isset($state['total']) ) {
      $this->total = $state['total'];
    }

    if ( isset($state['completed']) ) {
      $this->completed = $state['completed'];
    }

    if ( isset($state['limit']) ) {
      $this->limit = $state['limit'];
    }

    if ( isset($state['offset']) ) {
      $this->offset = $state['offset'];
    }
  }

  /**
   * Get batch of data to process. False - no more data
   * 
   * @return array|false
   */
  abstract public function get_batch();

  /**
   * Process single item. If returns false - the item is removed from the queue. Otherwise repeated.
   * 
   * @param mixed $item
   * @return mixed|false
   */
  abstract public function process_item($item);
}
