<?php
/**
 * Plugin Name: Simple Local Avatars
 * Plugin URI: https://wordpress.org/plugins/simple-local-avatars/
 *
 * Compatibility Description: Ensures compatibility with Simple Local Avatars plugin.
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\SimpleLocalAvatars')) {

    class SimpleLocalAvatars extends ICompatibility {
      protected $id = 'simple-local-avatars';
      protected $title = 'Simple Local Avatars';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_SLA';
      protected $description = 'Ensures compatibility with Simple Local Avatars plugin.';
      protected $plugin_file = 'simple-local-avatars/simple-local-avatars.php';

      public function module_init($sm){

      }
    }

  }

}