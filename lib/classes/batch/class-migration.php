<?php
/**
 * Batch Task 
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Batch;

abstract class Migration extends BatchTask {
  // Indicates whether we should stop querying new items
  protected $stop = false;

  public function get_state() {
    $state = parent::get_state();
    
    $state['id'] = $this->id;
    $state['is_migration'] = true;
    $state['stop'] = $this->stop;

    return $state;
  }

  public function set_state($state) {
    parent::set_state($state);

    $this->stop = $state['stop'] ?? false;
  }

  /**
   * Can be used to test if the migration should run
   * For example, if there are any old data that needs to be migrated
   */
  public function should_run() {
    return true;
  }
}
