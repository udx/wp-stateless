<?php

/**
 * Upgrader
 *
 * @since 1.2.0
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\Upgrader')) {

    final class Upgrader {

      /**
       * Upgrades data if needed.
       *
       */
      public static function call() {
        $version = get_site_option('wp_sm_version', false);

        if (!$version && is_multisite()) {
          $sites = get_sites();
          foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $version = get_option('wp_sm_version');
            if ($version) {
              restore_current_blog();
              break;
            }
            restore_current_blog();
          }
        }

        if (!$version) {
          self::fresh_install();
        }

        /* Maybe upgrade blog ( single site ) */
        self::upgrade();

        /* Maybe upgrade network options */
        if (ud_get_stateless_media()->is_network_detected(true)) {
          self::upgrade_network($version);
        }
      }

      /**
       * Upgrade current blog ( or single site )
       *
       */
      private static function upgrade() {
        global $wpdb;

        $version = get_option('wp_sm_version', false);

        /**
         * Upgrade to v.1.2.0 requirements
         */
        if (!$version || version_compare($version, '1.2.0', '<')) {

          if ($v = get_option('sm.mode')) {
            update_option('sm_mode', $v);
            delete_option('sm.mode');
          }

          $v = get_option('sm_mode');
          if ($v == '0') update_option('sm_mode', 'disabled');
          elseif ($v == '1') update_option('sm_mode', 'backup');
          elseif ($v == '2') update_option('sm_mode', 'cdn');

          if ($v = get_option('sm.service_account_name')) {
            update_option('sm_service_account_name', $v);
            delete_option('sm.service_account_name');
          }

          if ($v = get_option('sm.key_file_path')) {
            update_option('sm_key_file_path', $v);
            delete_option('sm.key_file_path');
          }

          if ($v = get_option('sm.bucket')) {
            update_option('sm_bucket', $v);
            delete_option('sm.bucket');
          }

          delete_option('sm.app_name');
          delete_option('sm.body_rewrite');
          delete_option('sm.bucket_url_path');
          delete_option('sm.post_content_rewrite');
        }

        update_option('dismissed_notice_stateless_cache_busting', true);
        if (!$version || version_compare($version, '2.1.7', '<')) {
          $sm_mode = get_option('sm_mode', null);
          $hashify_file_name = get_option('sm_hashify_file_name', null);
          if ($version && $sm_mode == 'stateless' && $hashify_file_name == 'true') {
            delete_option('dismissed_notice_stateless_cache_busting');
          }
        }

        if (!is_multisite() && $version && version_compare($version, '3.0', '<')) {
          self::migrate_root_dir();
          //updating mode name `stateless` to `ephemeral`
          $sm_mode = get_option('sm_mode');
          if ($sm_mode == 'stateless') update_option('sm_mode', 'ephemeral');
        }

        /**
         * Upgrade to v4.0
         */
        if ( $version && version_compare($version, '4.0', '<') ) {
          $modules = get_option('stateless-modules', []);
          
          if ( is_array($modules) && isset($modules['dynamic-image-support']) ) {
            update_option('sm_dynamic_image_support', $modules['dynamic-image-support']);

            unset($modules['dynamic-image-support']);
            update_option('stateless-modules', $modules);
          }
        }

        update_option('wp_sm_version', ud_get_stateless_media()->args['version']);
      }

      /**
       * Upgrade Network Enabled
       *
       */
      private static function upgrade_network($version) {
        /**
         * Upgrade to v.1.2.0 requirements
         */
        if (!$version || version_compare($version, '1.2.0', '<')) {

          if ($v = get_site_option('sm.key_file_path')) {
            update_site_option('sm_key_file_path', $v);
            delete_site_option('sm.key_file_path');
          }

          if ($v = get_option('sm.service_account_name')) {
            update_site_option('sm_service_account_name', $v);
            delete_site_option('sm.service_account_name');
          }
        }

        /**
         * Upgrade to v.3.0 requirements
         */
        if (is_multisite() && $version && version_compare($version, '3.0', '<')) {
          $sites = get_sites();
          foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            self::migrate_root_dir(true);
            //updating mode name `stateless` to `ephemeral`
            $sm_mode = get_option('sm_mode');
            if ($sm_mode == 'stateless') update_option('sm_mode', 'ephemeral');
            restore_current_blog();
          }

          //removing network option, because sites could uses different setting for Orginize media
          update_network_option(1, 'sm_root_dir', '');

          //forcing `Cache-Busting` for multisite to prevent replacing media with same filenames
          update_network_option(1, 'sm_hashify_file_name', 'true');

          $sm_mode_site = get_site_option('sm_mode');
          if ($sm_mode_site == 'stateless') update_site_option('sm_mode', 'ephemeral');
        }

        /**
         * Upgrade to v4.0
         */
        if ( $version && version_compare($version, '4.0', '<') ) {
          $modules = get_site_option('stateless-modules', []);
          
          if ( is_array($modules) && isset($modules['dynamic-image-support']) ) {
            update_site_option('sm_dynamic_image_support', $modules['dynamic-image-support']);

            unset($modules['dynamic-image-support']);
            update_site_option('stateless-modules', $modules);
          }
        }

        update_site_option('wp_sm_version', ud_get_stateless_media()->args['version']);
      }

      /**
       * Fresh install
       */
      private static function fresh_install() {
        if (is_multisite()) {
          $sites = get_sites();
          foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            self::migrate_root_dir(true, true);
            restore_current_blog();
          }
        }
      }

      /**
       *
       * @param bool $multisite pass true to use multisite prefix
       * @param bool $fresh_install for first install
       * @return string `sm_root_dir` value
       */
      private static function migrate_root_dir($multisite = false, $fresh_install = false) {
        $network_root_dir = get_network_option(1, 'sm_root_dir');
        $sm_root_dir      = $network_root_dir ? $network_root_dir : get_option('sm_root_dir', '');
        $organize_media   = get_option('uploads_use_yearmonth_folders');

        if ($organize_media == '1' && empty($sm_root_dir)) {
          $sm_root_dir  =  '/%date_year/date_month%/';
        } elseif (!empty($sm_root_dir) && $organize_media == '1') {
          $sm_root_dir  =  trim($sm_root_dir, ' /') . '/%date_year/date_month%/';
        } elseif (!empty($sm_root_dir)) {
          // $sm_root_dir  =  $sm_root_dir;
        } elseif ($organize_media != '1') {
          $sm_root_dir  =  '';
        }

        if ($multisite && $fresh_install) {
          if (empty($sm_root_dir) || $sm_root_dir[0] !== '/') {
            $sm_root_dir = "/" . $sm_root_dir;
          }
          if (false === strpos($sm_root_dir, '/sites/%site_id%')) {
            $sm_root_dir  =  '/sites/%site_id%' . $sm_root_dir;
          }
        }

        update_option('sm_root_dir', $sm_root_dir);

        if ($multisite) {
          //forcing `Cache-Busting` for multisite to prevent replacing media with same filenames
          update_option('sm_hashify_file_name', 'true');
        }

        return $sm_root_dir;
      }

      /**
       * Upgrade database, perform tasks that dbDelta can't do
       * 
       * @param string $new_version
       * @param string $old_version
       */
      public static function upgrade_db($new_version, $old_version) {
        global $wpdb;

        if ( !empty($old_version) && version_compare($old_version, '1.1', '<') ) {
          try {
            // Remove UNIQUE indexes, which will be recreated later using dbDelta as non-unique
            $wpdb->query('ALTER TABLE ' .  ud_stateless_db()->files . ' DROP INDEX post_id');

          } catch (\Throwable $e) {
            Helper::log($e->getMessage());
          }
        }

        if ( !empty($old_version) && version_compare($old_version, '1.2', '<') ) {
          try {
            // Remove UNIQUE indexes, which will be recreated later using dbDelta as non-unique
            $wpdb->query('ALTER TABLE ' .  ud_stateless_db()->files . ' DROP INDEX name');

          } catch (\Throwable $e) {
            Helper::log($e->getMessage());
          }
        }
      }
    }
  }
}
