<?php
/**
 *
 * @author palant@UD
 */
use wpCloud\StatelessMedia\Utility;

if( !class_exists( 'SM_CLI_Scaffold' ) ) {
  require_once( dirname( __FILE__ ) . '/class-sm-cli-scaffold.php' );
}

class SM_CLI_Sync extends SM_CLI_Scaffold {

  /**
   *
   */
  public $force = false;

  /**
   *
   */
  public $continue = false;

  /**
   *
   */
  public $fix = false;

  /**
   *
   */
  public $order = "";

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
   * Sync images
   *
   */
  public function images() {
    global $wpdb;

    /** Prepare arguments */
    $this->_prepare();

    $timer = time();

    $where = '';

    if ( $this->fix ) {
      $failed_attachments = Utility::sync_get_fails( 'cli_images' );

      if ( !empty($failed_attachments) ) {
        $where .= " AND ID IN (" . implode(',', $failed_attachments) . ") ";
      } else {
        WP_CLI::success( "Stateless Media is synced. No failed attachments." );
        exit;
      }
    }

    if ( $this->continue ) {
      $current_progress  = Utility::sync_retrieve_current_progress( 'cli_images' );

      if ( isset($current_progress[1]) && !empty( $current_progress[1] )) {
        $where .= " AND ID " . ( $this->order === 'DESC' ? ' < ' : ' > ' ) . $current_progress[1];
      }
    }

    if ( ! $this->force ) {
      $where .= " AND meta_id IS NULL " ;
    }

    /** Get Total Amount of Attachments */
    $this->total = $wpdb->get_var("
        SELECT
          COUNT(ID)
          FROM {$wpdb->posts}
          LEFT JOIN {$wpdb->postmeta} ON ID = post_id AND meta_key = 'sm_cloud'
          WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' " . $where . "
      ");


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
    $synced_images = 0;

    WP_CLI::line( 'Starting extract attachments.' );

    for( $this->start; $this->start < $this->end; $this->start += $this->limit ) {

      $limit = ( $this->end - $this->start ) < $this->limit ? ( $this->end - $this->start ) : $this->limit;

      $this->output( 'Synced: ' . $synced_images . '. Extracting from ' . ( $this->start + 1 ) . ' to ' . ( $this->start + $limit ) );

      /**
       * Get Attachments data.
       *
       */
      $attachments = $wpdb->get_results( $wpdb->prepare( "
        SELECT
          ID
          FROM {$wpdb->posts}
          LEFT JOIN {$wpdb->postmeta} ON ID = post_id AND meta_key = 'sm_cloud'
          WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' " . $where . " ORDER BY ID " . $this->order . "
          LIMIT %d, %d;
      ", $this->start, $limit ), ARRAY_A );

      if ( ! empty( $attachments ) ) {
        foreach ($attachments as $i => $a) {

          try {
            $response = $this->stateless_process_image($a['ID']);
            if (!empty($response)) {
              $synced_images++;
              $this->output($response);
            }
          } catch( \Exception $e ) {
            $this->output($e->getMessage());
          }

          /** Flush data */
          $wpdb->flush();
          @ob_flush();
          @flush();

        }

        unset($attachments);
      }

    }
    WP_CLI::success( "Stateless Media is synced" );

    WP_CLI::line( 'Media which have been checked: ' . number_format_i18n( $media_to_proceed ) );
    WP_CLI::line( 'Synced stateless for ' . number_format_i18n( $synced_images ) . ' attachments' );
    WP_CLI::line( 'Spent Time: ' . ( time() - $timer ) . ' sec' );

  }


  /**
   * Sync files
   *
   */
  public function files() {
    global $wpdb;

    /** Prepare arguments */
    $this->_prepare();

    $timer = time();

    $where = '';

    if ( $this->fix ) {
      $failed_attachments = Utility::sync_get_fails( 'cli_images' );

      if ( !empty( $failed_attachments ) ) {
        $where .= " AND ID IN (" . implode(',', $failed_attachments) . ") ";
      } else {
        WP_CLI::success( "Stateless Media is synced. No failed attachments." );
        exit;
      }
    }

    if ( $this->continue ) {
      $current_progress  = Utility::sync_retrieve_current_progress( 'cli_other' );

      if ( isset( $current_progress[1] ) && !empty( $current_progress[1] )) {
        $where .= " AND ID " . ( $this->order === 'DESC' ? ' < ' : ' > ' ) . $current_progress[1];
      }
    }

    if ( ! $this->force ) {
      $where .= " AND meta_id IS NULL " ;
    }

    /** Get Total Amount of Attachments */
    $this->total = $wpdb->get_var("
        SELECT
          COUNT(ID)
          FROM {$wpdb->posts}
          LEFT JOIN {$wpdb->postmeta} ON ID = post_id AND meta_key = 'sm_cloud'
          WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%' " . $where . "
      ");


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
    $synced_files = 0;

    WP_CLI::line( 'Starting extract attachments.' );

    for( $this->start; $this->start < $this->end; $this->start += $this->limit ) {

      $limit = ( $this->end - $this->start ) < $this->limit ? ( $this->end - $this->start ) : $this->limit;

      $this->output( 'Synced: ' . $synced_files . '. Extracting from ' . ( $this->start + 1 ) . ' to ' . ( $this->start + $limit ) );

      /**
       * Get Attachments data.
       *
       */
      $attachments = $wpdb->get_results( $wpdb->prepare( "
        SELECT
          ID
          FROM {$wpdb->posts}
          LEFT JOIN {$wpdb->postmeta} ON ID = post_id AND meta_key = 'sm_cloud'
          WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%' " . $where . " ORDER BY ID " . $this->order . "
          LIMIT %d, %d;
      ", $this->start, $limit ), ARRAY_A );

      if ( ! empty( $attachments ) ) {
        foreach ($attachments as $i => $a) {

          try {
            $response = $this->stateless_process_file($a['ID']);
            if (!empty($response)) {
              $synced_files++;
              $this->output($response);
            }
          } catch( \Exception $e ) {
            $this->output($e->getMessage());
          }

          /** Flush data */
          $wpdb->flush();
          @ob_flush();
          @flush();

        }

        unset($attachments);
      }

    }
    WP_CLI::success( "Stateless Media is synced" );

    WP_CLI::line( 'Media which have been checked: ' . number_format_i18n( $media_to_proceed ) );
    WP_CLI::line( 'Synced stateless for ' . number_format_i18n( $synced_files ) . ' attachments' );
    WP_CLI::line( 'Spent Time: ' . ( time() - $timer ) . ' sec' );

  }


  /**
   * @param $id
   * @return string
   * @throws Exception
   * Regenerate image sizes.
   */
  public function stateless_process_image( $id ) {

    if(ud_get_stateless_media()->is_connected_to_gs() !== true){
      //WP_CLI::line( 'Starting extract attachments.' );
      throw new \Exception( __( 'Not connected to GCS', ud_get_stateless_media()->domain) );
    }

    @error_reporting( 0 );
    timer_start();

    $image = get_post( $id );

    if ( ! $image || 'attachment' != $image->post_type || 'image/' != substr( $image->post_mime_type, 0, 6 ) )
      throw new \Exception( sprintf( __( 'Failed resize: %s is an invalid image ID.', ud_get_stateless_media()->domain ), esc_html( $id ) ) );

    $fullsizepath = get_attached_file( $image->ID );
    $upload_dir = wp_upload_dir();

    // If no file found
    if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {

      // Try get it and save
      $result_code = ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ), true, $id, "", $this->use_wildcards), true, $fullsizepath );

      if ( $result_code !== 200 ) {
        if(!Utility::sync_get_attachment_if_exist($image->ID, $fullsizepath)){ // Save file to local from proxy.
          Utility::sync_store_failed_attachment( $image->ID, 'cli_images' );
          throw new \Exception(sprintf(__('Both local and remote files are missing. Unable to resize. (%s)', ud_get_stateless_media()->domain), $image->guid));
        }
      }
    }

    @set_time_limit( -1 );

    //
    do_action( 'sm:pre::synced::image', $image->ID);
    $metadata = wp_generate_attachment_metadata( $image->ID, $fullsizepath );

    if(get_post_mime_type($image->ID) !== 'image/svg+xml'){
      if ( is_wp_error( $metadata ) ) {
        Utility::sync_store_failed_attachment( $image->ID, 'cli_images' );
        throw new \Exception($metadata->get_error_message());
      }

      if ( empty( $metadata ) ) {
        Utility::sync_store_failed_attachment( $image->ID, 'cli_images' );
        throw new \Exception(sprintf( __('No metadata generated for %1$s (ID %2$s).', ud_get_stateless_media()->domain), esc_html( get_the_title( $image->ID ) ), $image->ID));
      }
    }

    // If this fails, then it just means that nothing was changed (old value == new value)
    wp_update_attachment_metadata( $image->ID, $metadata );
    Utility::sync_store_current_progress( 'cli_images', $image->ID, true );
    Utility::sync_maybe_fix_failed_attachment( 'cli_images', $image->ID );
    do_action( 'sm:synced::image', $image->ID, $metadata);

    return sprintf( __( '%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain ), esc_html( get_the_title( $image->ID ) ), $image->ID, timer_stop() );
  }

  /**
   * @param $id
   * @return string
   * @throws \Exception
   */
  public function stateless_process_file( $id ) {

    @error_reporting( 0 );
    timer_start();

    if(ud_get_stateless_media()->is_connected_to_gs() !== true){
      throw new \Exception( __( 'Not connected to GCS', ud_get_stateless_media()->domain) );
    }

    $file = get_post( $id );

    if ( ! $file || 'attachment' != $file->post_type )
      throw new \Exception( sprintf( __( 'Attachment not found: %s is an invalid file ID.', ud_get_stateless_media()->domain ), esc_html( $id ) ) );

    $fullsizepath = get_attached_file( $file->ID );
    $local_file_exists = file_exists( $fullsizepath );

    $upload_dir = wp_upload_dir();

    if ( false === $fullsizepath || ! $local_file_exists ) {

      // Try get it and save
      $result_code = ud_get_stateless_media()->get_client()->get_media( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ), true, $fullsizepath );

      if ( $result_code !== 200 ) {
        if(!Utility::sync_get_attachment_if_exist($file->ID, $fullsizepath)){ // Save file to local from proxy.
          Utility::sync_store_failed_attachment( $file->ID, 'cli_other' );
          throw new \Exception(sprintf(__('File not found (%s)', ud_get_stateless_media()->domain), $file->guid));
        }
        else{
          $local_file_exists = true;
        }
      }
      else{
        $local_file_exists = true;
      }
    }

    if($local_file_exists){

      if ( !ud_get_stateless_media()->get_client()->media_exists( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ) ) ) {

        @set_time_limit( -1 );

        $metadata = wp_generate_attachment_metadata( $file->ID, $fullsizepath );

        if ( is_wp_error( $metadata ) ) {
          Utility::sync_store_failed_attachment( $file->ID, 'cli_other' );
          throw new \Exception($metadata->get_error_message());
        }

        wp_update_attachment_metadata( $file->ID, $metadata );
        do_action( 'sm:synced::nonImage', $file->ID, $metadata);

      }
      else{
        // Stateless mode: we don't need the local version.
        if(ud_get_stateless_media()->get( 'sm.mode' ) === 'ephemeral'){
          unlink($fullsizepath);
        }
      }

    }

    Utility::sync_store_current_progress( 'cli_other', $file->ID, true );
    Utility::sync_maybe_fix_failed_attachment( 'cli_other', $file->ID );

    return sprintf( __( '%1$s (ID %2$s) was successfully synchronised in %3$s seconds.', ud_get_stateless_media()->domain ), esc_html( get_the_title( $file->ID ) ), $file->ID, timer_stop() );
  }

  /**
   *
   */
  private function _prepare() {
    $args = $this->assoc_args;
    if( isset( $args[ 'b' ] ) ) {
      WP_CLI::error( 'Invalid parameter --b. Command must not be run directly with --b parameter.' );
    }
    $this->start          = isset( $args[ 'start' ] ) && is_numeric( $args[ 'start' ] ) ? $args[ 'start' ] : 0;
    $this->limit          = isset( $args[ 'limit' ] ) && is_numeric( $args[ 'limit' ] ) ? $args[ 'limit' ] : 100;
    $this->force          = isset( $args[ 'force' ] )         ? true : false;
    $this->continue       = isset( $args[ 'continue' ] )      ? true : false;
    $this->fix            = isset( $args[ 'fix' ] )           ? true : false;
    $this->use_wildcards  = isset( $args[ 'use_wildcards' ] ) ? true : false;
    $_REQUEST['use_wildcards'] = $this->use_wildcards;
    $this->order          = isset( $args[ 'order' ]) && $args[ 'order' ] === 'ASC' ? 'ASC' : 'DESC';
    if( isset( $args[ 'batch' ] ) ) {
      if( !is_numeric( $args[ 'batch' ] ) || $args[ 'batch' ] <= 0 ) {
        WP_CLI::error( 'Invalid parameter --batch' );
      }
      $this->batch = $args[ 'batch' ];
      $this->batches = isset( $args[ 'batches' ] ) ? $args[ 'batches' ] : 10;
      if( !is_numeric( $this->batches ) || $this->batches <= 0 ) {
        WP_CLI::error( 'Invalid parameter --batches' );
      } elseif ( $this->batch > $this->batches ) {
        WP_CLI::error( '--batch parameter must is invalid. It must not equal or less then --batches' );
      }
    } else {
      $this->end = isset( $args[ 'end' ] ) && is_numeric( $args[ 'end' ] ) ? $args[ 'end' ] : false;
    }
  }


}