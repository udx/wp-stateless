<?php
/**
 * Batch Task Interface
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Batch;

interface IBatchTask {

  /**
   * Initialize the state of the task
   */
  public function init_state();

  /**
   * Get human-friendly description
   */
  public function get_description();

  /**
   * Get the current state of the task
   */
  public function get_state();
  
  /**
   * Set/restore the current state of the task
   */
  public function set_state($state);

  /**
   * Get the next batch of items to process
   */
  public function get_batch();

  /**
   * Process the item
   */
  public function process_item($item);
}
