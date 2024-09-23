<?php

/**
 * Need to improve workflow.
 * Maybe add a transient of few days to keep track of synced files.
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\SyncNonMedia')) {

    class SyncNonMedia {

      /**
       * Status constants 
       */
      const STATUS_SYNCED = 'synced';
      const STATUS_QUEUED = 'queued';
      const STATUS_COPIED = 'copied';
      const STATUS_MOVED = 'moved';

      /**
       * @var array
       * 
       * List of directories to scan for compatibility files.
       */
      private $registered_dir = array();

      public function __construct() {
        // Manual sync using sync tab.
        // Return files to be manually sync from sync tab.
        add_filter('sm:sync::nonMediaFiles', array($this, 'sync_non_media_files'));
        add_filter('sm:sync::queue_is_exists', array($this, 'queue_is_exists'), 10, 2);

        // register a dir to sync from sync tab
        add_action('sm:sync::register_dir', array($this, 'register_dir'));
        add_action('sm:sync::addFile', array($this, 'add_file'), 10, 2);
        add_action('sm:sync::removeFile', array($this, 'remove_file'));

        // Sync a file.
        add_action('sm:sync::syncFile', array($this, 'sync_file'), 10, 4);
        add_action('sm:sync::copyFile', array($this, 'copy_file'), 10, 2);
        add_action('sm:sync::moveFile', array($this, 'move_file'), 10, 2);
        add_action('sm:sync::deleteFile', array($this, 'delete_file'));
        add_action('sm:sync::deleteFiles', array($this, 'delete_files'));
        add_action('sm:sync::unregister_file', array($this, 'unregister_file'), 10, 1);
      }

      /**
       * Register dir to be sync from Sync tab.
       * @param
       * $dir: The directory to register
       */
      public function register_dir($dir) {
        if (!in_array($dir, $this->registered_dir)) {
          $this->registered_dir[] = $dir;
        }
      }
      /**
       * Add file to list of files to be sync from Sync tab.
       * Save the file path to database.
       * @param array $media
       * @param string $status
       */
      public function add_file($media, $status = self::STATUS_QUEUED) {
        ud_stateless_db()->update_non_library_file( (array) $media, $status);
      }

      /**
       * Sync the file to GCS.
       * @param $name: Relative path to upload dir.
       * @param $absolutePath: Full path of the file
       * @param bool $forced: Type: bool/int; Whether to force to move the file to GCS even it's already exists.
       *                      true: Check whether it's already synced or not in database.
       *                      2 (int): Force to overwrite on GCS
       * @param array $args
       * @return bool|void $media: Media object returned from client->add_media() method.
       * @throws: Exception File not found@throws: Exception File not found
       */
      public function sync_file($name, $absolutePath, $forced = false, $args = array()) {
        $sm_mode = ud_get_stateless_media()->get('sm.mode');

        $args = wp_parse_args($args, array(
          'ephemeral' => true, // whether to delete local file in ephemeral mode.
          'download'  => false, // whether to delete local file in ephemeral mode.
          'use_root'  => 0,
          'skip_db'   => false,
          'manual_sync' => false,
          'remove_from_queue' => false, // removes entry from queue table if both file is missing.
          'name_with_root' => true,
        ));

        $args = apply_filters('sm:sync::syncArgs', $args, $name, $absolutePath, $forced);

        if ( apply_filters('sm:sync::queue_is_exists', $name, self::STATUS_SYNCED) && !$forced) {
          return false;
        }

        $file_type = Utility::mimetype_from_extension(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (empty($this->client)) {
          $this->client = ud_get_stateless_media()->get_client();
        }

        if (is_wp_error($this->client)) {
          return;
        }

        $file_copied_from_gcs = false;
        $local_file_exists = file_exists($absolutePath);

        do_action('sm::pre::sync::nonMediaFiles', $name, $absolutePath); // , $media

        if (!$local_file_exists && ($args['download'] || ud_get_stateless_media()->get('sm.mode') !== 'ephemeral' || ud_get_stateless_media()->get('sm.mode') !== 'stateless')) {
          // Try get it and save
          $result_code = $this->client->get_media($name, true, $absolutePath);

          if ($result_code == 200) {
            $local_file_exists = true;
            $file_copied_from_gcs = true;
          }
        }

        if ($local_file_exists && !$file_copied_from_gcs && !$args['download']) {

          if ($sm_mode == 'stateless' && !wp_doing_ajax()) {
            global $gs_client;

            $gs_name = apply_filters('wp_stateless_file_name', $name, $args['name_with_root']);

            //Bucket
            $bucket = ud_get_stateless_media()->get('sm.bucket');

            $bucket = $gs_client->bucket($bucket);
            $object = $bucket->object($gs_name);
            $args = wp_parse_args($args, array(
              'use_root' => $args['use_root'],
              'force' => ($forced == 2),
              'name' => $name,
              'absolutePath' => $absolutePath,
              'mimeType' => $file_type,
              'metadata' => array(
                'child-of' => dirname($name),
                'file-hash' => md5($name),
                'source' => $args['source'] ?? '',
                'sourceVersion' => $args['source_version'] ?? '',
              ),
              'is_webp' => '',
            ));

            $args = apply_filters('wp_stateless_add_media_args', $args);

            /**
             * Updating object metadata, ACL, CacheControl and contentDisposition
             * @return media object
             */
            try {
              $mediaOptions = array(
                'cacheControl' => apply_filters('sm:item:cacheControl', ud_get_stateless_media()->get_default_cache_control(), $absolutePath),
                'contentDisposition' => apply_filters('sm:item:contentDisposition', null, $absolutePath)
              );

              if ( !defined('WP_STATELESS_SKIP_ACL_SET') || !WP_STATELESS_SKIP_ACL_SET) {
                $mediaOptions['predefinedAcl'] = 'publicRead';
              }

              $media = $object->update(array('metadata' => $args['metadata']) + $mediaOptions);
            } catch (\Throwable $th) {
              //throw $th;
            }
          } else {
            $media = $this->client->add_media(array(
              'use_root' => $args['use_root'],
              'name' => $name,
              'force' => ($forced == 2),
              'absolutePath' => $absolutePath,
              'cacheControl' => apply_filters('sm:item:cacheControl', ud_get_stateless_media()->get_default_cache_control(), $absolutePath), //@todo use cacheControl from settings page.
              'contentDisposition' => apply_filters('sm:item:contentDisposition', null, $absolutePath),
              'mimeType' => $file_type,
              'metadata' => array(
                'child-of' => dirname($name),
                'file-hash' => md5($name),
                'source' => $args['source'] ?? '',
                'sourceVersion' => $args['source_version'] ?? '',
              ),
            ));
          }

          // Addon can hook this function to modify database after manual sync done.
          do_action('sm::synced::nonMediaFiles', $name, $absolutePath, $media); // , $media

          // Ephemeral mode: we don't need the local version.
          if ($args['ephemeral'] == true && ud_get_stateless_media()->get('sm.mode') === 'ephemeral') {
            unlink($absolutePath);
          }

          if (!$args['skip_db']) {
            // add file_path to the file list.
            do_action('sm:sync::addFile', $media, self::STATUS_SYNCED);
          }
          return $media;
        } elseif (!$local_file_exists && $args['remove_from_queue']) {
          if (!$this->client->media_exists($name)) {
            do_action('sm:sync::removeFile', $name);
            if ($args['manual_sync']) {
              throw new UnprocessableException(sprintf(__("Both local and remote files are missing. File: %s ", ud_get_stateless_media()->domain), $name));
            }
          }
        }
      }

      /**
       * Generate list for manual sync using sync tab. Sync all register files, dir and passed files.
       * @param array $files - Additional files to sync.
       * @return array
       */
      public function sync_non_media_files($files = array()) {
        $upload_dir = wp_upload_dir();
        $files = array_merge( $files, apply_filters('wp_stateless_get_non_library_files', array(), '') );
        foreach ($this->registered_dir as $key => $dir) {
          $dir = $upload_dir['basedir'] . "/" . trim($dir, '/') . "/";
          if (is_dir($dir)) {
            // Getting all the files from dir recursively.
            $_files = Utility::get_files($dir);
            // validating and adding to the $files array.
            foreach ($_files as $id => $file) {
              if (!file_exists($file)) {
                continue;
              }

              $_file = str_replace(wp_normalize_path($upload_dir['basedir']), '', wp_normalize_path($file));
              if (!in_array($_file, $files)) {
                $files[] = trim($_file, '/');
              }
            }
          }
        }

        // $files = array_values(array_unique($files));
        return $files;
      }

      /**
       * Delete a file from GCS.
       *
       * @param $file
       * @param bool $force
       * @return bool
       */
      public function delete_file($file) {
        try {
          $file = trim($file, '/');
          if (empty($this->client)) {
            $this->client = ud_get_stateless_media()->get_client();
          }

          if (is_wp_error($this->client)) {
            return false;
          }
          // Removing file for GCS
          $this->client->remove_media($file, "", 0);
          do_action('sm:sync::removeFile', $file);
          return true;
        } catch (\Exception $e) {
          return false;
        }
      }

      /**
       * Remove registered files of specified dir from GCS.
       *
       * @param $dir
       * @return bool|void
       */
      public function delete_files($dir) {
        if (empty($this->client)) {
          $this->client = ud_get_stateless_media()->get_client();
        }

        if (is_wp_error($this->client)) {
          return;
        }

        // Removing the files one by one.
        $files = apply_filters('wp_stateless_get_non_library_files', array(), $dir);

        if ( !empty($files) ) {
          foreach ($files as $key => $file) {
            if (strpos($file, $dir) !== false) {
              $this->client->remove_media($file, "", 0);
              do_action('sm:sync::removeFile', $file);
            }
          }
        }

        return true;
      }

      /**
       * Checks whether a file is exist in database.
       * @param $file: Path of file relative to upload dir.
       * @param string $status: optional. queued|synced
       * @return mixed: non boolean true. number of item found in db.
       */
      public function queue_is_exists($file, $status = '') {
        return ud_stateless_db()->get_non_library_file_id($file, $status);
      }

      /**
       * Deletes a entry from database.
       * @param $file: Path of file relative to upload dir.
       * @return mixed
       */
      public function remove_file($file) {
        ud_stateless_db()->remove_non_library_file($file);
      }

      /**
       * Delete a file from GCS.
       * @param $old_file
       * @param $new_file
       * @param bool $force
       * @param string $status
       * @return bool
       */
      public function copy_file($old_file, $new_file, $force = false, $status = self::STATUS_COPIED) {
        try {
          if ( !$force && apply_filters('sm:sync::queue_is_exists', $new_file, $status) ) {
            return false;
          }

          $client = $this->get_gs_client();

          // Copying file on GCS
          $media = $client->copy_media($old_file, $new_file);

          do_action('sm:sync::addFile', $media, $status);

          return true;
        } catch (\Exception $e) {
          return false;
        }
      }

      /**
       * Delete a file from GCS.
       * @param $old_file
       * @param $new_file
       * @return bool
       */
      public function move_file($old_file, $new_file) {
        try {

          $this->copy_file($old_file, $new_file, true, self::STATUS_MOVED);
          $this->delete_file($old_file);

          return true;
        } catch (\Exception $e) {
          return false;
        }
      }

      /**
       * Get GS Client
       * @return mixed
       */
      public function get_gs_client() {
        if (empty($this->client)) {
          $this->client = ud_get_stateless_media()->get_client();
        }

        return $this->client;
      }

      /**
       * Remove file from DB after file deletion.
       * @param string $file
       */
      public function unregister_file($file) {
        $file = str_replace( ud_get_stateless_media()->get_gs_path(), '', $file);
        $file = str_replace( ud_get_stateless_media()->get_gs_host(), '', $file);

        $file = trim($file, '/');

        do_action('sm:sync::removeFile', $file);
      }
    }
  }
}
