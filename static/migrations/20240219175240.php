<?php

use wpCloud\StatelessMedia\Batch\Migration;
use wpCloud\StatelessMedia\Helper;
use wpCloud\StatelessMedia\DB;

class Migration_20240219175240 extends Migration {
  const BATCH_SIZE = 1000;
  const LIMIT = 0;
  const DESC = true;

  protected $id = '20240219175240';

  public function should_run() {
    // Check if we have attachments to migrate
    global $wpdb;
    
    $sql = "SELECT ID " .
      "FROM $wpdb->posts posts " . 
      "WHERE posts.post_type = 'attachment' " . 
      "ORDER BY ID DESC " . 
      "LIMIT 1";

    $result = $wpdb->get_var( $sql );

    return !empty( $result );
  }

  public function init_state() {
    global $wpdb;

    parent::init_state();

    $this->description  = __( "Update data for Google Cloud files", ud_get_stateless_media()->domain );

    $order = self::DESC ? 'DESC' : 'ASC';

    // Getting the first/last attachment ID as a starting point
    $sql = "SELECT ID " .
      "FROM $wpdb->posts posts " . 
      "WHERE posts.post_type = 'attachment' " . 
      "ORDER BY ID $order " . 
      "LIMIT 1";

    $start_id = $wpdb->get_var( $sql );

    // Getting the total number of attachments
    $sql = "SELECT COUNT(*) " .
      "FROM $wpdb->posts posts " . 
      "WHERE posts.post_type = 'attachment' AND posts.post_status != 'trash'";

    $total = $wpdb->get_var( $sql );

    $this->total        = self::LIMIT > 0 ? min( self::LIMIT, $total ) : $total;
    $this->limit        = self::BATCH_SIZE;
    $this->offset       = self::DESC ? $start_id + 1 : $start_id - 1;
  }

  public function get_batch() {
    global $wpdb;

    $batch = [];

    if ( self::LIMIT > 0 && $this->completed >= self::LIMIT ) {
      $this->stop = true;
      return $batch;
    }

    if ($this->stop) {
      return $batch;
    } 

    $order = self::DESC ? 'DESC' : 'ASC';
    $condition = self::DESC ? 'posts.ID < %d' : 'posts.ID > %d';

    // Using last post ID instead of limit for performance
    $sql = "SELECT posts.ID " .
      "FROM $wpdb->posts posts " .
      "WHERE posts.post_type = 'attachment' AND $condition " .
      "ORDER BY posts.ID $order " .
      "LIMIT %d";

    $sql = $wpdb->prepare( $sql, $this->offset, $this->limit );

    $batch = $wpdb->get_col( $sql );

    $count = count( $batch );

    $this->offset = end( $batch );

    if ( $count < $this->limit ) {
      $this->stop = true;
    }
    
    return $batch;
  } 

  /**
   * Get the 'generation' field from the GCS media link
   * 
   * @param string $sm_id
   * @return string
   */
  private function _get_generation_from_media_link($media_link) {
    $query = parse_url($media_link, PHP_URL_QUERY);
    parse_str($query, $parts);

    return $parts['generation'] ?? 0;
  }

  /**
   * Get the file size from the meta or from the older version of meta
   * 
   * @param array $meta
   * @return int|null
   */
  private function _get_file_size($meta) {
    if ( isset( $meta['filesize'] ) ) {
      return $meta['filesize'];
    }

    if ( isset( $meta['object'] ) && isset( $meta['object']['size'] ) ) {
      return $meta['object']['size'];
    }

    return null;
  }

  /**
   * Get the width from the meta or from the WP attachment meta
   * 
   * @param array $meta
   * @param array $wp_meta
   * @return int|null
   */
  private function _get_width($meta, $wp_meta) {
    if ( isset( $meta['width'] ) ) {
      return $meta['width'];
    }

    if ( isset( $meta['object'] ) && isset( $meta['object']['metadata'] ) && isset( $meta['object']['metadata']['width'] ) ) {
      return $meta['object']['metadata']['width'];
    }

    if ( isset( $wp_meta['width'] ) ) {
      return $wp_meta['width'];
    }

    return null;
  }

  /**
   * Get the height from the meta or from the WP attachment meta
   * 
   * @param array $meta
   * @param array $wp_meta
   * @return int|null
   */
  private function _get_height($meta, $wp_meta) {
    if ( isset( $meta['height'] ) ) {
      return $meta['height'];
    }

    if ( isset( $meta['object'] ) && isset( $meta['object']['metadata'] ) && isset( $meta['object']['metadata']['height'] ) ) {
      return $meta['object']['metadata']['height'];
    }

    if ( isset( $wp_meta['height'] ) ) {
      return $wp_meta['height'];
    }

    return null;
  }

  /**
   * Get the content type from the meta or from the file
   * 
   * @param array $meta
   * @param string $file
   * @return string
   * @throws \Exception
   */
  private function _get_content_type($meta, $file) {
    if ( isset( $meta['contentType'] ) ) {
      return $meta['contentType'];
    }

    if ( isset( $meta['object'] ) && isset( $meta['object']['contentType'] ) ) {
      return $meta['object']['contentType'];
    }

    // Get mimetype based on file extension the file extension
    $file = pathinfo($file, PATHINFO_BASENAME);
    $type = wp_check_filetype($file);
    
    return $type['type'] ?? '';
  }

  /**
   * Get the self link from the meta
   * 
   * @param array $meta
   * @return string
   * @throws \Exception
   */
  private function _get_self_link($meta) {
    if ( isset($meta['selfLink']) ) {
      return $meta['selfLink'];
    }

    if ( !isset($meta['mediaLink']) ) {
      throw new \Exception('Media link not defined');
    }

    $link = $meta['mediaLink'] ?? '';
    $remove = '/download';

    $pos = strpos($link, $remove);
  
    if ($pos === false) {
      return $link;
    }

    $link = substr_replace($link, '', $pos, strlen($remove));
    $parts = explode('?', $link);
  
    return reset($parts);
  }

  /**
   * Get the version from the meta
   * 
   * @param array $meta
   * @return string
   */
  private function _get_version($meta) {
    return isset($meta['sm_version']) ? $meta['sm_version'] : $this->id;
  }

  /**
   * Get the file link from the meta or generate a new one
   * 
   * @param string $name
   * @param array $meta
   * @return string
   */
  private function _get_file_link($name, $meta) {
    return isset($meta['fileLink']) ? $meta['fileLink'] : ud_stateless_db()->get_file_link($name);
  }

  public function process_item($item) {
    global $wpdb;
    
    Helper::log('Processing item ' . $item);

    $meta = get_post_meta( $item, 'sm_cloud', true );

    if ( !$meta || empty($meta) ) {
      return false;
    }

    $wp_meta = get_post_meta( $item, '_wp_attachment_metadata', true );

    $old_suppress = $wpdb->suppress_errors();

    // Disable autocommit and use transactions to ensure data integrity
    $wpdb->query( 'SET autocommit = 0;' );
    $wpdb->query( 'START TRANSACTION;' );

    try {
      // Update file data
      $name = $meta['name'] ?? '';

      $data = [
        'post_id'               => $item,
        'name'                  => $name,
        'bucket'                => $meta['bucket'] ?? '',
        'generation'            => $this->_get_generation_from_media_link( $meta['mediaLink'] ?? ''),
        'cache_control'         => $meta['cacheControl'] ?? null,
        'content_type'          => $this->_get_content_type($meta, $name),
        'content_disposition'   => $meta['contentDisposition'] ?? null,
        'file_size'             => $this->_get_file_size($meta),
        'width'                 => $this->_get_width($meta, $wp_meta),
        'height'                => $this->_get_height($meta, $wp_meta),
        'stateless_version'     => $this->_get_version($meta),
        'storage_class'         => $meta['storageClass'] ?? null,
        'file_link'             => $this->_get_file_link($name, $meta),
        'self_link'             => $this->_get_self_link($meta),
      ];

      $wpdb->insert(ud_stateless_db()->files, $data);
  
      // Update file sizes data
      $sizes = $meta['sizes'] ?? [];
    
      foreach ($sizes as $size => $size_data) {
        $name = $size_data['name'];

        $data = [
          'post_id'               => $item,
          'name'                  => $name,
          'size_name'             => $size,
          'generation'            => $this->_get_generation_from_media_link( $size_data['mediaLink'] ),
          'file_size'             => $this->_get_file_size($size_data),
          'width'                 => $this->_get_width($size_data, $wp_meta['sizes'][$size] ?? []),
          'height'                => $this->_get_height($size_data, $wp_meta['sizes'][$size] ?? []),
          'file_link'             => $this->_get_file_link($name, $meta),
          'self_link'             => $this->_get_self_link($size_data),
        ];
  
        $wpdb->insert(ud_stateless_db()->file_sizes, $data);
      }

      // Update file meta data ('fileMd5' for LiteSpeed Cache)
      $key = 'fileMd5';
      $md5_data = $meta[$key] ?? null;

      if ( !empty($md5_data) ) {
        $data = [
          'post_id'               => $item,
          'meta_key'              => sanitize_key($key),
          'meta_value'            => maybe_serialize($md5_data),
        ];
  
        $wpdb->insert(ud_stateless_db()->file_meta, $data);
      }

      $wpdb->query( 'COMMIT' );

      $this->completed++;

    } catch ( \Throwable $e ) {
      $wpdb->query( 'ROLLBACK;' );

      Helper::log( "Error while processing item $item: " . $e->getMessage() );
    }

    $wpdb->query( 'SET autocommit = 1;' );

    $wpdb->suppress_errors($old_suppress);

    return false;
  }

}