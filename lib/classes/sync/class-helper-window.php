<?php

namespace wpCloud\StatelessMedia\Sync;

/**
 * 
 */
class HelperWindow {
  public $title;
  public $content;

  /**
   * 
   */
  public function __construct($title, $content) {
    $this->title = $title;
    $this->content = $content;
  }

  /**
   * 
   */
  public function jsonSerialize() {
    return get_object_vars($this);
  }
}
