<?php

if (!class_exists('UDX_WP_Async_Request')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-async-request.php', 'dir');
}

if (!class_exists('UDX_WP_Background_Process')) {
  require_once ud_get_stateless_media()->path('lib/ns-vendor/classes/deliciousbrains/wp-background-processing/classes/wp-background-process.php', 'dir');
}

abstract class BackgroundSync extends UDX_WP_Background_Process {
}
