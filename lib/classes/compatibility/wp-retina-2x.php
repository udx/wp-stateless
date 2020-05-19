<?php
/**
 * Plugin Name: WP Retina 2x Pro
 * Plugin URI: https://store.meowapps.com/products/wp-retina-2x-pro/
 *
 * Compatibility Description: Ensures compatibility with WP Retina 2x Pro plugin.
 *
 *
 * Reference: https://github.com/wpCloud/wp-stateless/issues/380
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\WPRetina2x' ) ) {
    class WPRetina2x extends ICompatibility {
      protected $id = 'wp-retina-2x-pro';
      protected $title = 'WP Retina 2x Pro';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_WP_RETINA_2X';
      protected $description = 'Ensures compatibility with WP Retina 2x Pro plugins. Make sure <b>"Over HTTP Check"</b> setting is <b>enabled</b>.';
      protected $plugin_file = 'wp-retina-2x-pro/wp-retina-2x-pro.php';
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        // Sync image.
        // wr2x_before_generate_retina is always called
        // where wr2x_before_regenerate called from ajax requests.
        add_action( 'wr2x_before_generate_retina', array( $this, 'before_generate_retina' ) );
        add_action( 'wr2x_retina_file_added', array( $this, 'retina_file_added' ), 10, 3 );

        // Delete retina image from GCS.
        add_action( 'delete_attachment', array( $this, 'delete_retina' ) );
        // Manual Sync retina images.
        add_action( 'sm:synced::image', array( $this, 'manual_sync_retina' ), 10, 2 );

        $over_http = get_option( 'wr2x_over_http_check', false );
        if( !$over_http ) {
          $url = admin_url( 'admin.php?page=wr2x_settings-menu' );
          ud_get_stateless_media()->errors->add( array( 'key' => "wp-retina-2x-pro-over-http-check", 'title' => sprintf( __( "WP Stateless Compatibility: WP Retina 2x Pro", ud_get_stateless_media()->domain ) ), 'message' => sprintf( __( 'Please enable the <b>"Over HTTP Check"</b> settings in <b>Meow Apps</b> > <a href="%s">Retina</a>.', ud_get_stateless_media()->domain ), $url ), ), 'notice' );
        }
      }

      /**
       * Download image from GCS to server if doesn't exists.
       *
       * @param int $attachment_id
       * @return void
       */
      public function before_generate_retina( $attachment_id ) {
        $upload_basedir = wp_upload_dir();
        $upload_basedir = trailingslashit( $upload_basedir[ 'basedir' ] );
        $metadata = wp_get_attachment_metadata( $attachment_id );
        $image_sizes = Utility::get_path_and_url( $metadata, $attachment_id );

        foreach( $image_sizes as $image ) {
          if( !empty( $image[ 'gs_name' ] ) && !file_exists( $image[ 'path' ] ) ) {
            ud_get_stateless_media()->get_client()->get_media( $image[ 'gs_name' ], true, $image[ 'path' ] );
          }
        }
      }

      /**
       * Upload retina image to GCS.
       *
       * @param int $attachment_id
       * @param String $retina_file
       * @param String $name image size
       * @return void
       */
      public function retina_file_added( $attachment_id, $retina_file, $name ) {
        $gs_name = apply_filters( 'wp_stateless_file_name', $retina_file, 0 );
        do_action( 'sm:sync::syncFile', $gs_name, $retina_file, true, array( 'use_root' => true ) );
      }

      /**
       * Delete retina image from GCS
       *
       * @param int $attachment_id
       * @return void
       */
      public function delete_retina( $attachment_id ) {
        $metadata = wp_get_attachment_metadata( $attachment_id );
        $image_sizes = Utility::get_path_and_url( $metadata, $attachment_id );
        $ignore = get_option( "wr2x_ignore_sizes" );
        if( empty( $ignore ) ) $ignore = array();

        foreach( $image_sizes as $size => $img ) {
          if( in_array( $size, $ignore ) ) {
            continue;
          }
          $pathinfo = pathinfo( $img[ 'gs_name' ] );
          $gs_name_retina = trailingslashit( $pathinfo[ 'dirname' ] ) . $pathinfo[ 'filename' ] . '@2x.' . $pathinfo[ 'extension' ];
          // @todo Sometime relevant file don't exist on GCS. Try to skip those when retina don't exist.
          do_action( 'sm:sync::deleteFile', $gs_name_retina );
        }
      }

      /**
       * Sync retina images when manual sync is triggered.
       *
       * @param int $attachment_id
       * @param array $metadata
       * @return void
       */
      public function manual_sync_retina( $attachment_id, $metadata ) {
        $image_sizes = Utility::get_path_and_url( $metadata, $attachment_id );

        $ignore = get_option( "wr2x_ignore_sizes" );
        if( empty( $ignore ) ) $ignore = array();

        foreach( $image_sizes as $size => $img ) {
          if( in_array( $size, $ignore ) ) {
            continue;
          }

          $pathinfo       = pathinfo( $img['path'] ) ;
          $retina_file    = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . '@2x.' . $pathinfo['extension'];
          $gs_name        = apply_filters( 'wp_stateless_file_name', $retina_file, 0);

          // @todo Sometime relevant file don't exist on GCS. Try to skip those when retina don't exist.
          do_action( 'sm:sync::syncFile', $gs_name, $retina_file, false, array( 'use_root' => true ) );
        }
      }
    }
  }

}
