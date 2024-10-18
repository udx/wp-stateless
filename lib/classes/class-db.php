<?php

/**
 * DB
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia {


  if (!class_exists('wpCloud\StatelessMedia\DB')) {

    class DB {
      use Singleton;

      const DB_VERSION_KEY = 'sm_db_version';
      const DB_VERSION = '1.2';
      const FULL_SIZE = '__full';

      /**
       * @var \WPDB
       */
      private $wpdb;

      /**
       * Bucket link
       */
      private $bucket_link = '';

      /**
       * Files table name
       */
      private $files = '';

      /**
       * File sizes table name
       */
      private $file_sizes = '';

      /**
       * File meta table name
       */
      private $file_meta = '';

      /**
       * Cache group name
       */
      private $cache_group = 'stateless_media';

      /**
       * Files table fields mapping, used for backward compatibility with old postmeta
       */
      private $file_mapping = [
        'id' => 'id',
        'post_id' => 'post_id',
        'bucket' => 'bucket',
        'name' => 'name',
        'generation' => 'generation',
        'cacheControl' => 'cache_control',
        'contentType' => 'content_type',
        'contentDisposition' => 'content_disposition',
        'filesize' => 'file_size',
        'width' => 'width',
        'height' => 'height',
        'stateless_version' => 'stateless_version',
        'storageClass' => 'storage_class',
        'fileLink' => 'file_link',
        'selfLink' => 'self_link',
      ];

      /**
       * Files sizes table fields mapping, used for backward compatibility with old postmeta
       */
      private $file_sizes_mapping = [
        'id' => 'id',
        'post_id' => 'post_id',
        'size_name' => 'size_name',
        'name' => 'name',
        'generation' => 'generation',
        'filesize' => 'file_size',
        'width' => 'width',
        'height' => 'height',
        'fileLink' => 'file_link',
        'selfLink' => 'self_link',
      ];

      protected function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->files = $this->wpdb->prefix . 'stateless_files';
        $this->file_sizes = $this->wpdb->prefix . 'stateless_file_sizes';
        $this->file_meta = $this->wpdb->prefix . 'stateless_file_meta';

        $image_host = ud_get_stateless_media()->get_gs_host();
        $this->bucket_link = apply_filters('wp_stateless_bucket_link', $image_host);

        if ( is_multisite() ) {
          $this->cache_group = implode('_', [
            $this->cache_group,
            get_current_blog_id(),
          ]);
        }

        $this->_init();
      }

      /**
       * Init hooks
       */
      private function _init() {
        add_filter('wp_stateless_generate_cloud_meta', [$this, 'process_cloud_meta'], 10, 5);
        add_action('deleted_post', [$this, 'delete_post'], 10, 2);

        add_filter('wp_stateless_get_file', [$this, 'get_file'], 10, 3);
        add_filter('wp_stateless_get_file_sizes', [$this, 'get_file_sizes'], 10, 2);
        add_filter('wp_stateless_get_file_meta', [$this, 'get_file_meta'], 10, 2);
        add_filter('wp_stateless_get_file_meta_value', [$this, 'get_file_meta_value'], 10, 4);
        add_action('wp_stateless_set_file', [$this, 'set_file'], 10, 2);
        add_action('wp_stateless_set_file_size', [$this, 'set_file_size'], 10, 3);
        add_action('wp_stateless_set_file_meta', [$this, 'update_file_meta'], 10, 3);
        add_action('wp_stateless_get_non_library_files', [$this, 'get_non_library_files'], 10, 2);
      }

      /**
       * Getters
       */
      public function __get($property) {
        if ( in_array($property, ['files', 'file_sizes', 'file_meta']) ) {
          return $this->$property;
        }
      }

      /**
       * Creates or updates DB structure
       */
      public function create_db() {
        $version = get_option(self::DB_VERSION_KEY, '');

        if ($version === self::DB_VERSION) {
          return;
        }

        try {
          Upgrader::upgrade_db(self::DB_VERSION, $version);

          $charset_collate = $this->wpdb->get_charset_collate();

          /**
           * Indexes for varchar(255) columns are limited for backward compatibility.
           * Based on wp-admin/includes/schema.php (wp_get_db_schema function)
           */

          $sql = "CREATE TABLE $this->files (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` bigint(20) unsigned NULL DEFAULT NULL,
            `bucket` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `generation` bigint(16) NOT NULL,
            `cache_control` varchar(255) NULL DEFAULT NULL,
            `content_type` varchar(255) NULL DEFAULT NULL,
            `content_disposition` varchar(100) NULL DEFAULT NULL,
            `file_size` bigint(20) unsigned NULL DEFAULT NULL,
            `width` int unsigned NULL DEFAULT NULL,
            `height` int unsigned NULL DEFAULT NULL,
            `stateless_version` varchar(20) NOT NULL,
            `storage_class` varchar(50) NULL DEFAULT NULL,
            `file_link` text NOT NULL,
            `self_link` text NOT NULL,
            `source` varchar(50) NULL DEFAULT NULL,
            `source_version` varchar(50) NULL DEFAULT NULL,
            `status` varchar(10) NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY post_id (post_id),
            KEY `name` (`name`(191)),
            UNIQUE KEY post_id_name (post_id, `name`(150))
          ) $charset_collate;
  
          CREATE TABLE $this->file_sizes (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` bigint(20) unsigned NULL DEFAULT NULL,
            `size_name` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `generation` bigint(16) NOT NULL,
            `file_size` bigint(20) unsigned NULL DEFAULT NULL,
            `width` int unsigned NULL DEFAULT NULL,
            `height` int unsigned NULL DEFAULT NULL,
            `file_link` text NOT NULL,
            `self_link` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY post_id (post_id),
            UNIQUE KEY post_id_size (post_id, size_name(150))
          ) $charset_collate;
  
          CREATE TABLE $this->file_meta (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` bigint(20) unsigned NULL DEFAULT NULL,
            `meta_key` varchar(255) NOT NULL,
            `meta_value` longtext NOT NULL,
            PRIMARY KEY (`id`),
            KEY post_id (post_id),
            UNIQUE KEY post_id_key (post_id, meta_key(150))
          ) $charset_collate;";
  
          require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          dbDelta($sql);
  
          update_option(self::DB_VERSION_KEY, self::DB_VERSION);
        } catch (\Throwable $e) {
          Helper::log($e->getMessage());
        }
      }

      /**
       * Remove custom DB table on plugin uninstall
       * 
       * @param int $site_id
       */
      public function clear_db($site_id) {
        switch_to_blog($site_id);

        $tables = array(
          $this->wpdb->prefix . 'files',
          $this->wpdb->prefix . 'files_sizes',
          $this->wpdb->prefix . 'file_meta',
        );

        $tables = implode(', ', $tables);

        $sql = "DROP TABLE IF EXISTS $tables;";
        $this->wpdb->query($sql);

        restore_current_blog();
      }

      /**
       * Get file link
       * 
       * @param string $name
       * @return string
       */
      public function get_file_link($name) {
        return trailingslashit($this->bucket_link) . $name;
      }

      /**
       * Get file row ID by post ID
       * 
       * @param int $post_id
       * @return int | null
       */
      public function get_file_id($post_id) {
        $sql = "SELECT id FROM $this->files WHERE post_id = %d AND post_id IS NOT NULL";
        $id = $this->wpdb->get_var( $this->wpdb->prepare($sql, $post_id) );

        return $id ? (int) $id : null;
      }

      /**
       * Get ID of the non-library file by file name and status 
       * 
       * @param string $name
       * @param string $status
       * @return int | null
       */
      public function get_non_library_file_id($name, $status = '') {
        $sql = "SELECT id FROM $this->files WHERE name = %s AND post_id IS NULL";
        $args = [$name];

        if ( !empty($status) ) {
          $sql .= " AND status = %s";
          $args[] = $status;
        }

        $id = $this->wpdb->get_var( $this->wpdb->prepare($sql, ...$args) );

        return $id ? (int) $id : null;
      }

      /**
       * Get name of the non-library file by part of the name 
       * 
       * @param string $name
       * @return string | null
       */
      public function get_non_library_file_name($name) {
        $sql = "SELECT name FROM $this->files WHERE name like '%%%s' AND post_id IS NULL";

        return $this->wpdb->get_var( $this->wpdb->prepare($sql, $name) );
      }

      /**
       * Get file size row ID by post ID and size name
       * 
       * @param int $post_id
       * @param string $size
       * @return int | null
       */
      public function get_file_size_id($post_id, $size) {
        $sql = "SELECT id FROM $this->file_sizes WHERE post_id = %d AND size_name = %s";
        $id = $this->wpdb->get_var( $this->wpdb->prepare($sql, $post_id, $size) );

        return $id ? (int) $id : null;
      }

      /**
       * Get file meta row ID by post ID and meta key
       * 
       * @param int $post_id
       * @param string $key
       * @return int | null
       */
      public function get_file_meta_id($post_id, $key) {
        $key = sanitize_key($key);

        $sql = "SELECT id FROM $this->file_meta WHERE post_id = %d AND meta_key = %s";
        $id = $this->wpdb->get_var( $this->wpdb->prepare($sql, $post_id, $key) );

        return $id ? (int) $id : null;
      }

      /**
       * Add file data to DB
       * 
       * @param array $cloud_meta   - generated WP postmeta
       * @param array $media        - GCS media object
       * @param string $image_size  - image size name
       * @param array $img          - image (or image size) data for upload
       * @param string $bucketLink  - bucket link
       * @return mixed
       */
      public function process_cloud_meta($cloud_meta, $media, $image_size, $img, $bucketLink) {
        $this->update_data($media);

        return $cloud_meta;
      }
      
      /**
       * Determine which data to update and run updates
       * 
       * @param array $media - GCS media object
       */
      public function update_data($media) {
        $error = false;

        if ( !isset($media['name']) || !isset($media['metadata']) || !isset($media['metadata']['size']) ) {
          $error = 'Metadata missing or incorrect for GCS media object';
        } else {
          $size = $media['metadata']['size'];

          if ( $size == self::FULL_SIZE ) {
            $attachment_id = isset($media['metadata']['object-id']) ? $media['metadata']['object-id'] : null;
  
            if ( !$attachment_id ) {
              $error = 'Unable to get attachment ID for GCS media object';
            } else {
              $this->_update_file(
                $attachment_id, 
                $this->_get_file_from_media($media),
              );
            }
          } else {
            $attachment_id = isset($media['metadata']['child-of']) ? $media['metadata']['child-of'] : null;
  
            if ( !$attachment_id ) {
              $error = 'Unable to get parent attachment ID for GCS media object';
            } else {
              $this->_update_file_size(
                $attachment_id, 
                $size, 
                $this->_get_file_size_from_media($media),
              );
            }
          }
        }

        if ( $error ) {
          Helper::log($error);
          Helper::log($media, true);
        }
      }

      /**
       * Update file data, based on the mapping received from the media object
       * 
       * @param int $attachment_id
       * @param array $data
       * @return int | null
       */
      public function set_file($attachment_id, $data) {
        $result = [];

        foreach ($this->file_mapping as $key => $mapping) {
          if ( isset($data[$key]) ) {
            $result[$mapping] = $data[$key];
            
            continue;
          }

          if ( isset($data[$mapping]) ) {
            $result[$mapping] = $data[$mapping];

            continue;
          }
        }

        unset($result['id']);

        return $this->_update_file($attachment_id, $result);
      }

      /**
       * Update file size data
       * 
       * @param int $attachment_id
       * @param string $size_name
       * @param array $data
       * @return int | null
       */
      public function set_file_size($attachment_id, $size_name, $data) {
        $result = [];

        foreach ($this->file_sizes_mapping as $key => $mapping) {
          if ( isset($data[$key]) ) {
            $result[$mapping] = $data[$key];
            
            continue;
          }

          if ( isset($data[$mapping]) ) {
            $result[$mapping] = $data[$mapping];

            continue;
          }
        }

        unset($result['id']);

        return $this->_update_file_size($attachment_id, $size_name, $result);
      }

      /**
       * Convert GSC media object into stateless file data
       * 
       * @param array $media
       * @return array
       */
      private function _get_file_from_media($media, $status = '') {
        $name = $media['name'];

        $data =[
          'bucket' => $media['bucket'] ?? '',
          'name' => $name,
          'generation' => $media['generation'] ?? '',
          'cache_control' => $media['cacheControl'] ?? null,
          'content_type' => $media['contentType'] ?? null,
          'content_disposition' => $media['contentDisposition'] ?? null,
          'file_size' => $media['size'] ?? null,
          'width' => $media['metadata']['width'] ?? null,
          'height' => $media['metadata']['height'] ?? null,
          'stateless_version' => get_option('wp_sm_version', false),
          'storage_class' => $media['storageClass'] ?? null,
          'file_link' => $this->get_file_link($name),
          'self_link' => $media['selfLink'] ?? '',
          'status' => $status,
          'source' => $media['metadata']['source'] ?? '',
          'source_version' => $media['metadata']['sourceVersion'] ?? '',
        ];

        return $data;
      }

      /**
       * Update non Media Library file (compatibility files)
       * 
       * @param array $media - GCS media object
       * @param string $source - source of the file
       * @param string $status - status of the file
       */
      public function update_non_library_file($media, $status = '') {
        if ( !is_array($media) || empty($media) || !isset($media['name']) ) {
          Helper::log('Media object is not valid or empty. Unable to update non-library file.');

          return;
        }

        $data = $this->_get_file_from_media($media, $status);
        $file_id = $this->get_non_library_file_id($data['name'], $status);

        if ( $file_id ) {
          $this->wpdb->update(
            $this->files,
            $data,
            ['id' => $file_id]
          );
        } else {
          $this->wpdb->insert( $this->files, $data );

          $file_id = $this->wpdb->insert_id;
        }

        return $file_id;
      }        
        
      /**
       * Update file data
       * 
       * @param int $attachment_id
       * @param array $data
       * @return int | null
       */
      private function _update_file($attachment_id, $data) {
        $file_id = $this->get_file_id($attachment_id);

        if ( $file_id ) {
          $this->wpdb->update(
            $this->files,
            $data,
            ['id' => $file_id]
          );
        } else {
          $data['post_id'] = $attachment_id;

          $this->wpdb->insert( $this->files, $data );

          $file_id = $this->wpdb->insert_id;
        }

        $this->_delete_file_cache($attachment_id);

        return $file_id;
      }

      /**
       * Convert GSC media object into stateless file size data
       * 
       * @param array $media
       * @return array
       */
      private function _get_file_size_from_media($media) {
        $name = $media['name'];

        $data =[
          'name' => $name,
          'generation' => $media['generation'] ?? '',
          'file_size' => $media['size'] ?? null,
          'width' => $media['metadata']['width'] ?? null,
          'height' => $media['metadata']['height'] ?? null,
          'file_link' => $this->get_file_link($name),
          'self_link' => $media['selfLink'] ?? '',
        ];

        return $data;
      }

      /**
       * Update file size data
       * 
       * @param int $attachment_id
       * @param string $size_name
       * @param array $media
       * @return int | null
       */
      private function _update_file_size($attachment_id, $size_name, $data) {        
        $file_size_id = $this->get_file_size_id($attachment_id, $size_name);

        if ( $file_size_id ) {
          $this->wpdb->update(
            $this->file_sizes,
            $data,
            ['id' => $file_size_id]
          );
        } else {
          $data['post_id'] = $attachment_id;
          $data['size_name'] = $size_name;

          $this->wpdb->insert( $this->file_sizes, $data );

          $file_size_id = $this->wpdb->insert_id;
        }

        $this->_delete_file_sizes_cache($attachment_id);

        return $file_size_id;
      }

      /**
       * Delete file data when attachment is deleted
       * 
       * @param int $post_id
       * @param \WP_Post $post
       */
      public function delete_post($post_id, $post) {
        $file_id = $this->get_file_id($post_id);

        if ( !$file_id ) {
          return;
        }

        $this->wpdb->delete(
          $this->files,
          ['post_id' => $post_id]
        );

        $this->wpdb->delete(
          $this->file_sizes,
          ['post_id' => $post_id]
        );

        $this->wpdb->delete(
          $this->file_meta,
          ['post_id' => $post_id]
        );

        $this->_delete_attachment_cache($post_id);
      }

      /**
       * Map requested fields into database query< compatible with the old post meta structure
       * 
       * @param array $fields
       * @param array $fields_mapping
       * @return string
       */
      private function _map_fields($fields, $fields_mapping) {
        $mapped = [];
        $result = [];

        if ( !is_array($fields) ) {
          $fields = [$fields];
        }

        if (empty($fields)) {
          $fields = array_keys($fields_mapping);
        }

        foreach ($fields as $key) {
          if ( isset($fields_mapping[$key]) ) {
            $mapped[$key] = $fields_mapping[$key];  
          }
        }

        foreach ($mapped as $key => $value) {
          $result[] = "$value AS $key";
        }

        return implode(', ', $result);
      }

      /**
       * Get cache key for file data
       * 
       * @param int $post_id
       * @return string
       */
      private function _get_file_cache_key($post_id) {
        return implode('_', ['file', $post_id]);
      }

      /**
       * Get cache key for file sizes data
       * 
       * @param int $post_id
       * @return string
       */
      private function _get_file_sizes_cache_key($post_id) {
        return implode('_', ['file_sizes', $post_id]);
      }

      /**
       * Get cache group for file meta data
       * 
       * @param int $post_id
       * @return string
       */
      private function _get_file_meta_cache_group($post_id) {
        return implode('_', [$this->cache_group, 'meta', $post_id]);
      }

      /**
       * Get cache key for file meta data
       * 
       * @param int $post_id
       * @param string $key
       * @return string
       */
      private function _get_file_meta_cache_key($post_id, $key) {
        return implode('_', [$key, $post_id]);
      }

      /**
       * Get cache for file meta
       * 
       * @param int $post_id
       * @param string $key
       * @param bool $found
       * @return mixed
       */
      private function _get_file_meta_cache($post_id, $key, &$found) {
        return wp_cache_get(
          $this->_get_file_meta_cache_key($post_id, $key), 
          $this->_get_file_meta_cache_group($post_id), 
          false, 
          $found
        );
      }

      /**
       * Set cache for file meta
       * 
       * @param int $post_id
       * @param string $key
       * @param mixed $value
       * @return bool
       */
      private function _set_file_meta_cache($post_id, $key, $value) {
        return wp_cache_set(
          $this->_get_file_meta_cache_key($post_id, $key), 
          $value, 
          $this->_get_file_meta_cache_group($post_id)
        );
      }

      /**
       * Delete file cache
       * 
       * @param int $attachment_id
       */
      private function _delete_file_cache($attachment_id) {
        wp_cache_delete( $this->_get_file_cache_key($attachment_id), $this->cache_group );
      }

      /**
       * Delete file sizes cache
       * 
       * @param int $attachment_id
       */
      private function _delete_file_sizes_cache($attachment_id) {
        wp_cache_delete( $this->_get_file_sizes_cache_key($attachment_id), $this->cache_group );
      }

      /**
       * Delete file meta cache for single key
       * 
       * @param int $attachment_id
       * @param string $key
       */
      private function _delete_file_meta_key_cache($attachment_id, $key) {
        wp_cache_delete( 
          $this->_get_file_meta_cache_key($attachment_id, $key), 
          $this->_get_file_meta_cache_group($attachment_id) 
        );
      }

      /**
       * Delete all file meta cache
       * 
       * @param int $attachment_id
       */
      private function _delete_file_meta_cache($attachment_id) {
        wp_cache_flush_group( $this->_get_file_meta_cache_group($attachment_id) );
      }

      /**
       * Delete all attachment cache
       * 
       * @param int $attachment_id
       */
      private function _delete_attachment_cache($attachment_id) {
        $this->_delete_file_cache($attachment_id);
        $this->_delete_file_sizes_cache($attachment_id);
        $this->_delete_file_meta_cache($attachment_id);
      }

      /**
       * Get the total files count known to WP-Stateless
       * 
       * @return int
       */
      public function get_total_files() {
        global $wpdb;

        try {
          $query = "SELECT COUNT(id) FROM $this->files";

          return $wpdb->get_var($query);
        } catch (\Throwable $e) {
          return 0;
        }
      }

      /**
       * Get the total file sizes count known to WP-Stateless
       * 
       * @return int
       */
      public function get_total_file_sizes() {
        global $wpdb;

        try {
          $query = "SELECT COUNT(id) FROM $this->file_sizes";

          return $wpdb->get_var($query);
        } catch (\Throwable $e) {
          return 0;
        }
      }

      /**
       * Get the total non-media file sizes count known to WP-Stateless
       * 
       * @return int
       */
      public function get_total_non_media_files() {
        global $wpdb;

        try {
          $query = "SELECT COUNT(id) FROM $this->files WHERE post_id IS NULL";

          return $wpdb->get_var($query);
        } catch (\Throwable $e) {
          return 0;
        }
      }

      /**
       * Get file data. If $with_sizes is set to true, all sizes will be included
       * 
       * @param array $meta
       * @param int $attachment_id
       * @param bool $with_sizes
       * @return array
       */
      public function get_file($meta, $attachment_id, $with_sizes = false) {
        if ( ud_get_stateless_media()->get('sm.use_postmeta') ) {
          $meta = get_post_meta($attachment_id, 'sm_cloud', true);

          if ( !empty($meta) ) {
            return $meta;
          }
        }

        // Get values from the cache
        $cache_key = $this->_get_file_cache_key($attachment_id);

        $meta = wp_cache_get($cache_key, $this->cache_group, false, $found);

        if ( $found ) {
          return $meta;
        }

        // Get values from the DB
        $fields = $this->_map_fields([], $this->file_mapping);

        $sql = "SELECT $fields FROM $this->files WHERE post_id = %d";

        $meta = $this->wpdb->get_row( $this->wpdb->prepare($sql, $attachment_id), ARRAY_A );

        if ( empty($meta) ) {
          return get_post_meta($attachment_id, 'sm_cloud', true);
        }

        wp_cache_set($cache_key, $meta, $this->cache_group);

        // Get file size meta data
        if ( $with_sizes ) {
          $meta['sizes'] = apply_filters('wp_stateless_get_file_sizes', [], $attachment_id);
        }

        return $meta;
      }

      /**
       * Get file sizes data
       * 
       * @param array $sizes
       * @param int $attachment_id
       * @return array
       */
      public function get_file_sizes($sizes, $attachment_id) {
        if ( ud_get_stateless_media()->get('sm.use_postmeta') ) {
          $meta = get_post_meta($attachment_id, 'sm_cloud', true);
          return isset($meta['sizes']) ? $meta['sizes'] : [];
        }

        // Get values from the cache
        $cache_key = $this->_get_file_sizes_cache_key($attachment_id);

        $sizes = wp_cache_get($cache_key, $this->cache_group, false, $found);

        if ( $found ) {
          return $sizes;
        }

        // Get values from the DB
        $fields = $this->_map_fields([], $this->file_sizes_mapping);

        $sql = "SELECT $fields FROM $this->file_sizes WHERE post_id = %d";
        $sql = $this->wpdb->prepare($sql, $attachment_id);

        $result = $this->wpdb->get_results( $this->wpdb->prepare($sql, $attachment_id), ARRAY_A );
        $sizes = [];

        if ( !empty($result) ) {
          foreach ($result as $size) {
            $size_name = $size['size_name'];
            unset($size['size_name']);
            $sizes[$size_name] = $size;
          }
        }

        wp_cache_set($cache_key, $sizes, $this->cache_group);

        return $sizes;
      }

      /**
       * Get file meta
       * 
       * @param int $post_id
       * @param string $key
       * @param mixed $default
       * @return mixed
       */
      public function get_file_meta_value($value, $post_id, $key, $default = null) {
        if ( ud_get_stateless_media()->get('sm.use_postmeta') ) {
          $meta = get_post_meta($post_id, 'sm_cloud', []);

          return isset($meta[$key]) ? $meta[$key] : $default;
        }

        // Get values from the cache
        $key = sanitize_key($key);

        $value = $this->_get_file_meta_cache($post_id, $key, $found);

        if ( $found ) {
          return $value;
        }

        // Get values from the DB
        $sql = "SELECT meta_value FROM $this->file_meta WHERE post_id = %d AND meta_key = %s";
        $data = $this->wpdb->get_var( $this->wpdb->prepare($sql, $post_id, $key) );

        $value = null;

        if ( is_serialized( $data ) ) { // Don't attempt to unserialize data that wasn't serialized going in.
          $value = @unserialize( trim( $data ) );
        } else {
          $value = $data;
        }

        $this->_set_file_meta_cache($post_id, $key, $value);

        return $value;
      }

      /**
       * Update file meta
       * 
       * @param int $post_id
       * @param string $key
       * @param mixed $value
       * @return int
       */
      public function update_file_meta($post_id, $key, $value) {
        $key = sanitize_key($key);

        // Update value in the DB
        $value = maybe_serialize( $value );

        $file_meta_id = $this->get_file_meta_id($post_id, $key);

        if ( $file_meta_id ) {
          $this->wpdb->update(
            $this->file_meta,
            ['meta_value' => $value],
            ['id' => $file_meta_id],
          );
        } else {
          $this->wpdb->insert( $this->file_meta, [
            'meta_key' => $key,
            'meta_value' => $value,
            'post_id' => $post_id,
          ]);

          $file_meta_id = $this->wpdb->insert_id;
        }

        $this->_delete_file_meta_key_cache($post_id, $key);

        return $file_meta_id;
      }

      /**
       * Get all the meta for the file
       * 
       * @param mixed $meta
       * @param int $post_id
       * @return mixed
       */
      public function get_file_meta($meta, $post_id) {
        $sql = "SELECT meta_key, meta_value FROM $this->file_meta WHERE post_id = %d";
        
        $result = $this->wpdb->get_results( $this->wpdb->prepare($sql, $post_id), ARRAY_A );

        $data = [];

        foreach ($result as $row) {
          $key = $row['meta_key'];
          $value = $row['meta_value'];

          if ( is_serialized( $value ) ) { // Don't attempt to unserialize data that wasn't serialized going in.
            $value = @unserialize( trim( $value ) );
          }

          $data[$key] = $value;
        }

        return $data;
      }

      /**
       * Get file row ID by file name 
       * 
       * @param string $name
       * @return int | null
       */
      public function remove_non_library_file($name) {
        return $this->wpdb->delete(
          $this->files,
          [
            'name' => $name, 
            'post_id' => null,
          ]
        );
      }

      /**
       * Get all non-library files 
       * 
       * @param array $files
       * @param string $prefix
       * @return array | null
       */
      public function get_non_library_files($files, $prefix = '') {
        if ( !empty($prefix) ) {
          $sql = $this->wpdb->prepare("SELECT name FROM $this->files WHERE post_id IS NULL AND name LIKE '%s'", $this->wpdb->esc_like($prefix) . '%');
        } else {
          $sql = "SELECT name FROM $this->files WHERE post_id IS NULL";
        }

        return $this->wpdb->get_col($sql);
      }
    }
  }
}
