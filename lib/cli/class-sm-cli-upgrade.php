<?php
/**
 *
 * @author peshkov@UD
 */

if( !class_exists( 'SM_CLI_Scaffold' ) ) {
  require_once( dirname( __FILE__ ) . '/class-sm-cli-scaffold.php' );
}

class SM_CLI_Upgrade extends SM_CLI_Scaffold {

  /**
   *
   */
  public $start = false;
  
  /**
   *
   */
  public $end = false;
  
  /**
   *
   */
  public $limit = false;
  
  /**
   *
   */
  public $batch = false;
  
  /**
   *
   */
  public $batches = false;
  
  /**
   *
   */
  public $total = 0;
  
  /**
   *
   */
  public $log;

  /**
   * Upgrade Meta.
   *
   */
  public function meta() {
    global $wpdb;
    
    /** Prepare arguments */
    $this->_prepare();
    
    $timer = time();

    /** Get Total Amount of Attachments */
    $this->total = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
    
    if( $this->batch ) {
      $this->output( "Running Batch {$this->batch} from {$this->batches}" );
      $range = round( $this->total / $this->batches );
      $this->start = ( $this->batch * $range ) - $range;
      $this->end = $this->batches == $this->batch ? $this->total : $this->batch * $range;
      $this->output( "Starting from {$this->start} row. " );
      $this->output( "And proceeding up to {$this->end} row." );
    } else {
      $this->output( "Running in default way. Starting from {$this->start} row and proceeding up to end." );
      $this->end = $this->end ? $this->end : $this->total;
    }
    $media_to_proceed = $this->end - $this->start;
    
    //** Counters */
    $upgraded_meta = 0;
    
    WP_CLI::line( 'Starting extract attachments.' );
    
    for( $this->start; $this->start < $this->end; $this->start += $this->limit ) {
      
      $limit = ( $this->end - $this->start ) < $this->limit ? ( $this->end - $this->start ) : $this->limit;
      
      $this->output( 'Upgraded: ' . $upgraded_meta . '. Extracting from ' . ( $this->start + 1 ) . ' to ' . ( $this->start + $limit ) );

      /**
       * Get Attachments data.
       *
       */
      $attachments = $wpdb->get_results( $wpdb->prepare( "
        SELECT 
          ID
          FROM {$wpdb->posts}
          WHERE post_type = 'attachment'
          LIMIT %d, %d;
      ", $this->start, $limit ), ARRAY_A );
      
      //print_r( $attachments ); die();
      
      foreach( $attachments as $i => $a ) {

        if( $this->_maybe_upgrade_meta( $a[ 'ID' ] ) ) {
          $upgraded_meta++;
        }

        /** Flush data */
        $wpdb->flush();
        @ob_flush();
        @flush();
        
      }
      
      unset( $attachments );
      
    }

    WP_CLI::success( "Stateless Media Meta is upgraded" );

    WP_CLI::line( 'Media which have been checked: ' . number_format_i18n( $media_to_proceed ) );
    WP_CLI::line( 'Upgraded stateless meta for ' . number_format_i18n( $upgraded_meta ) . ' attachments' );
    WP_CLI::line( 'Spent Time: ' . ( time() - $timer ) . ' sec' );
    
  }

  /**
   * Maybe Upgrade Stateless meta for specific attachment
   */
  private function _maybe_upgrade_meta( $id ) {
    global $wpdb;

    $bool = false;

    /* Determine if attachment has legacy main meta data  */
    $name = get_post_meta( $id, 'sm_cloud:name', true );
    $fileLink = get_post_meta( $id, 'sm_cloud:fileLink', true );

    /* Let's upgrade our shit. */
    if( !empty( $name ) && !empty( $fileLink ) ) {

      /* Disable autocommit to Database to prevent broken balance transactions. */
      $wpdb->query( 'SET autocommit = 0;' );
      $wpdb->query( 'START TRANSACTION;' );

      try {

        $cloud_meta = array(
          'name' => $name,
          'fileLink' => $fileLink,
          'id' => get_post_meta( $id, 'sm_cloud:id', true ),
          'storageClass' => get_post_meta( $id, 'sm_cloud:storageClass', true ),
          'mediaLink' => get_post_meta( $id, 'sm_cloud:mediaLink', true ),
          'selfLink' => get_post_meta( $id, 'sm_cloud:selfLink', true ),
          'bucket' => get_post_meta( $id, 'sm_cloud:bucket', true ),
          'object' => get_post_meta( $id, 'sm_cloud:object', true ),
          'sizes' => array(),
        );

        delete_post_meta( $id, 'sm_cloud:name' );
        delete_post_meta( $id, 'sm_cloud:fileLink' );
        delete_post_meta( $id, 'sm_cloud:id' );
        delete_post_meta( $id, 'sm_cloud:storageClass' );
        delete_post_meta( $id, 'sm_cloud:mediaLink' );
        delete_post_meta( $id, 'sm_cloud:selfLink' );
        delete_post_meta( $id, 'sm_cloud:bucket' );
        delete_post_meta( $id, 'sm_cloud:object' );

        $metadata = wp_get_attachment_metadata( $id );

        if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {

          foreach( $metadata[ 'sizes' ] as $image_size => $data ) {

            $name = get_post_meta( $id, 'sm_cloud:' . $image_size . ':name', true );
            $fileLink = get_post_meta( $id, 'sm_cloud:' . $image_size . ':fileLink', true );

            if( !empty( $name ) && !empty( $fileLink ) ) {
              $cloud_meta[ 'sizes' ][ $image_size ] = array(
                'id' => get_post_meta( $id, 'sm_cloud:' . $image_size . ':id', true ),
                'name' => $name,
                'fileLink' => $fileLink,
                'mediaLink' => get_post_meta( $id, 'sm_cloud:' . $image_size . ':mediaLink', true ),
                'selfLink' => get_post_meta( $id, 'sm_cloud:' . $image_size . ':selfLink', true ),
              );
            }

            delete_post_meta( $id, 'sm_cloud:' . $image_size . ':name' );
            delete_post_meta( $id, 'sm_cloud:' . $image_size . ':fileLink' );
            delete_post_meta( $id, 'sm_cloud:' . $image_size . ':id' );
            delete_post_meta( $id, 'sm_cloud:' . $image_size . ':mediaLink' );
            delete_post_meta( $id, 'sm_cloud:' . $image_size . ':selfLink' );

          }

        }

        if( !empty( $cloud_meta ) ) {
          update_post_meta( $id, 'sm_cloud', $cloud_meta );
        }

        $bool = true;

      } catch ( \Exception $e ) {

        /* Rollback all transactions to prevent broken orders, order items, etc. */
        $wpdb->query( 'ROLLBACK' );
        $wpdb->query( 'SET autocommit = 1;' );

        WP_CLI::warning( "SM Meta for attachment #{$id} was not upgraded due the error: " . $e->getMessage() );

        return $bool;
      }

      /* Commit all transactions to Database and enable autocommit again. */
      $wpdb->query( 'COMMIT' );
      $wpdb->query( 'SET autocommit = 1;' );

    }

    return $bool;
  }
  
  /**
   *
   */
  private function _prepare() {
    $args = $this->assoc_args;
    if( isset( $args[ 'b' ] ) ) {
      WP_CLI::error( 'Invalid parameter --b. Command must not be run directly with --b parameter.' );
    }
    $this->start = isset( $args[ 'start' ] ) && is_numeric( $args[ 'start' ] ) ? $args[ 'start' ] : 0;
    $this->limit = isset( $args[ 'limit' ] ) && is_numeric( $args[ 'limit' ] ) ? $args[ 'limit' ] : 100;
    if( isset( $args[ 'batch' ] ) ) {
      if( !is_numeric( $args[ 'batch' ] ) || $args[ 'batch' ] <= 0 ) {
        WP_CLI::error( 'Invalid parameter --batch' );
      }
      $this->batch = $args[ 'batch' ];
      $this->batches = isset( $args[ 'batches' ] ) ? $args[ 'batches' ] : 10;
      if( !is_numeric( $args[ 'batches' ] ) || $args[ 'batches' ] <= 0 ) {
        WP_CLI::error( 'Invalid parameter --batches' );
      } elseif ( $this->batch > $this->batches ) {
        WP_CLI::error( '--batch parameter must is invalid. It must not equal or less then --batches' );
      }
    } else {
      $this->end = isset( $args[ 'end' ] ) && is_numeric( $args[ 'end' ] ) ? $args[ 'end' ] : false;
    }
  }

}