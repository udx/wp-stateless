<?php

if (!class_exists('UDX_WP_Async_Request')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-async-request.php', 'dir');
}

if (!class_exists('UDX_WP_Background_Process')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-background-process.php', 'dir');
}

class BackgroundImageSync extends UDX_WP_Background_Process {

  protected $action = 'background_image_sync';

  public function task($item) {
    error_log('BGS Item' . print_r($item, 1));

    return false;
  }
}
