<?php

/**
 * Compatibility Plugin Name: BuddyPress
 * Compatibility Plugin URI: https://wordpress.org/plugins/buddypress/
 *
 * Compatibility Description: Ensures compatibility with BuddyPress.
 *
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\BuddyPress')) {

    class BuddyPress extends Compatibility {
      const AVATARS = 'avatars/';
      const BLOG_AVATARS = 'blog-avatars/';
      const GROUP_AVATARS = 'group-avatars/';

      protected $id = 'buddypress';
      protected $title = 'BuddyPress';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_BUDDYPRESS';
      protected $description = 'Ensures compatibility with BuddyPress.';
      protected $plugin_file = ['buddypress/bp-loader.php'];
      protected $sm_mode_not_supported = ['stateless'];
      protected $enabled = false;
      protected $is_internal = true;

      /**
       * @param $sm
       */
      public function module_init($sm) {
        add_action('xprofile_avatar_uploaded', array($this, 'avatar_uploaded'), 10, 3);
        add_action('groups_avatar_uploaded', array($this, 'avatar_uploaded'), 10, 3);

        add_filter('bp_core_avatar_folder_url', array($this, 'bp_core_avatar_folder_url'), 10, 4);
        add_filter('bp_core_avatar_folder_dir', array($this, 'bp_core_avatar_folder_dir'), 10, 4);
        add_filter('stateless_skip_cache_busting', array($this, 'skip_cache_busting'), 20, 2);
        add_filter('sm:sync::syncArgs', array($this, 'sync_args'), 10, 4);
        add_filter('bp_core_pre_delete_existing_avatar', array($this, 'delete_existing_avatar'), 10, 2);
        add_filter('bp_attachments_pre_get_attachment', array($this, 'bp_attachments_pre_get_attachment'), 10, 2);
      }

      /**
       * Check if the file is in BP avatar directory.
       */
      protected function is_avatar_dir($name) {
        return strpos($name, self::AVATARS) === 0 || strpos($name, self::BLOG_AVATARS) === 0 || strpos($name, self::GROUP_AVATARS) === 0;
      }

      /**
       * Sync avatar.
       * @param $item_id
       * @param $type
       * @param $r
       */
      public function avatar_uploaded($item_id, $type, $r) {
        $full_avatar = bp_core_fetch_avatar(array(
          'object'  => $r['object'],
          'item_id' => $r['item_id'],
          'html'    => false,
          'type'    => 'full',
        ));
        $thumb_avatar = bp_core_fetch_avatar(array(
          'object'  => $r['object'],
          'item_id' => $r['item_id'],
          'html'    => false,
          'type'    => 'thumb',
        ));

        foreach ( [$full_avatar, $thumb_avatar] as $url ) {
          $name = apply_filters('wp_stateless_file_name', $url, 0);
          $absolutePath = apply_filters('wp_stateless_addon_files_root', ''); 
          $absolutePath .= '/' . $name;

          do_action( 'sm:sync::syncFile', $name, $absolutePath );
        }
      }

      /**
       * Deleting avatar from GCS.
       * @param $return
       * @param $args
       * @return bool
       */
      public function delete_existing_avatar($return, $args) {
        if (empty($args['object']) && empty($args['item_id'])) {
          return $return;
        }

        $full_avatar = bp_core_fetch_avatar(array('object' => $args['object'], 'item_id' => $args['item_id'], 'html' => false, 'type' => 'full',));
        $thumb_avatar = bp_core_fetch_avatar(array('object' => $args['object'], 'item_id' => $args['item_id'], 'html' => false, 'type' => 'thumb',));

        do_action('sm:sync::deleteFile', apply_filters('wp_stateless_file_name', $full_avatar, 0));
        do_action('sm:sync::deleteFile', apply_filters('wp_stateless_file_name', $thumb_avatar, 0));

        if ( ud_get_stateless_media()->is_mode( ['ephemeral', 'stateless'] ) ) {
          $return = false;
        }

        return $return;
      }

      /**
       * Sync and return GCS url for group images.
       *
       * Used as CSS background-image.
       *
       * @param [type] $return
       * @param [type] $r
       * @return void
       */
      public function bp_attachments_pre_get_attachment($return, $r) {
        // Return if this is a recursive call.
        if (!empty($r['recursive'])) {
          return $return;
        }

        try {
          $debug_backtrace = \debug_backtrace(false);

          // Making sure we only return GCS link if the type is url.
          if (!empty($debug_backtrace[3]['args'][0]) && $debug_backtrace[3]['args'][0] == 'url') {
            $r['recursive'] = true;

            $url = bp_attachments_get_attachment('url', $r);
            $name = apply_filters('wp_stateless_file_name', $url, 0);

            $root_dir = ud_get_stateless_media()->get('sm.root_dir');
            $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);
            $root_dir = trim($root_dir, '/ '); // Remove any forward slash and empty space.

            if (!empty($name) && $root_dir . "/" != $name) {
              $full_path = bp_attachments_get_attachment(false, $r);
              do_action('sm:sync::syncFile', $name, $full_path, false, array('ephemeral' => false));
              $return = ud_get_stateless_media()->get_gs_host() . '/' . $name;
            }
          }
        } catch (\Throwable $th) {
          //throw $th;
        }
        return $return;
      }

      /**
       * Skip cache busting while Buddypress processes images.
       * 
       * @param $return
       * @param $filename
       * @return mixed
       */
      public function skip_cache_busting($return, $filename) {
        $back_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($back_trace as $trace) {
          if ( isset($trace['file']) && strpos($trace['file'], 'buddypress') !== false ) {
            if ( isset($trace['function']) && $trace['function'] === 'sanitize_file_name' ) {
              return $filename;
            }
          }
        }

        return $return;
      }

      /**
       * Update args when uploading/syncing file to GCS.
       * 
       * @param array $args
       * @param string $name
       * @param string $file
       * @param bool $force
       * 
       * @return array
       */
      public function sync_args($args, $name, $file, $force) {
        if ( !$this->is_avatar_dir($name) ) {
          return $args;
        }

        if ( ud_get_stateless_media()->is_mode('stateless') ) {
          $args['name_with_root'] = false;
        }

        $args['source'] = 'BuddyPress';
        $args['source_version'] = '';

        try {
          $args['source_version'] = bp_get_version();
        } catch (\Throwable $th) {
        }

        return $args;
      }

      /**
       * Override BP avatar folder URL.
       */
      public function bp_core_avatar_folder_url($folder_url, $item_id, $object, $avatar_dir) {
        if ( ud_get_stateless_media()->is_mode( ['disabled', 'backup'] ) ) {
          return $folder_url;
        }

        $position = strpos($folder_url, $avatar_dir);

        if ( $position === false ) {
          return $folder_url;
        }

        $url = substr($folder_url, $position);
        $url = apply_filters('wp_stateless_addon_files_url', '', $url);

        return $url;
      }

      /**
       * Override BP avatar folder.
       */
      public function bp_core_avatar_folder_dir($folder_dir, $item_id, $object, $avatar_dir) {
        if ( !ud_get_stateless_media()->is_mode('stateless') ) {
          return $folder_dir;
        } 
        
        $position = strpos($folder_dir, $avatar_dir);

        if ( $position === false ) {
          return $folder_dir;
        }

        $dir = substr($folder_dir, $position);
        $dir = apply_filters('wp_stateless_addon_files_url', '', $dir);

        return $dir;
      }
    }
  }
}
