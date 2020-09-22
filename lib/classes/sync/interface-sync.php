<?php

namespace wpCloud\StatelessMedia\Sync;

interface ISync {

  /**
   * Start Process from begining.
   * Needs to be implemented separately for each kind of sync.
   */
  public function start();

  /**
   * Fill queue with more items if available.
   * Needs to be implemented separately for each kind of sync.
   */
  public function extend_queue();

  /**
   * Implementation of the count of items for this sync type.
   */
  public function get_total_items();

  /**
   * Sync Process needs to have a display-able name
   */
  public function get_name();

  /**
   * Helper window. Return false if no window needed.
   */
  public function get_helper_window();
}
