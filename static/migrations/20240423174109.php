<?php

use wpCloud\StatelessMedia\Batch\Migration;
use wpCloud\StatelessMedia\Helper;

class Migration_20240423174109 extends Migration {
  const BATCH_SIZE = 10;
  const LIMIT = 0;
  const DESC = true;

  protected $id = '20240423174109';

  public function should_run() {
    // Check if we have attachments to migrate
    global $wpdb;
    $table_name = $wpdb->prefix . 'sm_sync';
    $result = null;

    try {
      $result = $wpdb->get_var("SELECT id FROM $table_name LIMIT 1");
    } catch (\Throwable $e) {
      Helper::log('Table sm_sync not found');

      return false;
    }

    return !empty( $result );
  }

  public function init_state() {
    global $wpdb;

    parent::init_state();

    $this->description  = __( "Optimize Compatibility Files", ud_get_stateless_media()->domain );

    $table_name = $wpdb->prefix . 'sm_sync';
    $order = self::DESC ? 'DESC' : 'ASC';
    $total = 0;

    // Getting the total number of attachments
    try {
      $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    } catch (\Throwable $e) {
      Helper::log('Table sm_sync not found');

      return false;
    }

    $this->total = self::LIMIT > 0 ? min( self::LIMIT, $total ) : $total;
    $this->limit = self::LIMIT > 0 ? min(self::BATCH_SIZE, self::LIMIT) : self::BATCH_SIZE;
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
    $table_name = $wpdb->prefix . 'sm_sync';

    $sql = "SELECT id " .
      "FROM $table_name " .
      "ORDER BY id $order " .
      "LIMIT %d OFFSET %d";

    try {
      $sql = $wpdb->prepare( $sql, $this->limit, $this->offset );

      $batch = $wpdb->get_col( $sql );
  
      $count = count( $batch );
  
      $this->offset += $count;
  
      if ( $count < $this->limit ) {
        $this->stop = true;
      }
    } catch (\Throwable $e) {
      Helper::log( $e->getMessage() );
    }

    return $batch;
  } 

  public function process_item($item) {
    global $wpdb;

    Helper::log('Processing item ' . $item);

    $old_suppress = $wpdb->suppress_errors();
    $sync_table = $wpdb->prefix . 'sm_sync';
    $files_table = ud_stateless_db()->files;

    try {
      $sql = "SELECT st.file as file, st.status as status, ft.id as fid " . 
        "FROM $sync_table st " . 
        "LEFT JOIN $files_table ft ON st.file = ft.name AND ft.post_id IS NULL " . 
        "WHERE st.id = %d";

      $sql = $wpdb->prepare( $sql, $item );

      $sync = $wpdb->get_row( $sql );
  
      // We have the data and the data is missing in 'wp_stateless_files'
      if ( !empty($sync) && !empty($sync->file) && empty($sync->fid) ) {
        $client = ud_get_stateless_media()->get_client();

        $media = (array) $client->get_media( $sync->file );

        if ( is_array($media) && !empty($media) && isset($media['metadata']) ) {
          $metadata = $media['metadata'];

          if ( !isset($metadata['source']) && !isset($metadata['sourceVersion']) ) {
            $media['metadata']['sourceVersion'] = $this->id;
          }

          ud_stateless_db()->update_non_library_file($media, $sync->status);
        }
      }
    } catch (\Throwable $e) {
      Helper::log( $e->getMessage() );
    }

    $wpdb->suppress_errors($old_suppress);

    $this->completed++;

    return false;
  }

}