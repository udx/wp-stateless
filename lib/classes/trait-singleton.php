<?php

namespace wpCloud\StatelessMedia;

/**
 * Class Singleton
 * @package wpCloud\StatelessMedia
 */
trait Singleton {

  /**
   * @var null
   */
  private static $instance = null;

  /**
   * Instance
   *
   * @return null|Singleton
   */
  public static function instance() {
    return self::$instance ? self::$instance : self::$instance = new self;
  }
}
