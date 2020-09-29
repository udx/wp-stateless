<?php

namespace wpCloud\StatelessMedia\Sync;

/**
 * Helper Window for a single process UI
 */
class HelperWindow {

  /**
   * Title
   */
  public $title;

  /**
   * Content
   */
  public $content;

  /**
   * Construct
   */
  public function __construct($title, $content) {
    $this->title = $title;
    $this->content = $content;
  }

  /**
   * To JSON
   */
  public function jsonSerialize() {
    return get_object_vars($this);
  }
}
