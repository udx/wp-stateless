<?php

/**
 * Plugin and environment status class
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia;

use wpCloud\StatelessMedia\Status\Migrations;
use wpCloud\StatelessMedia\Status\Info;

class Status {
  use Singleton;

  protected function __construct() {
    Info::instance();
    Migrations::instance();
  }
} 