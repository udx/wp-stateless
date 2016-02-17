<?php
/**
 * AJAX Handler
 *
 * @since 1.0.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Ajax' ) ) {

    final class Ajax {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'stateless_process_image',
        'get_images_media_ids',
        'get_other_media_ids',
        'stateless_process_file'
      );

      /**
       * The list of wp_ajax_nopriv_{name} actions
       *
       * @var array
       */
      var $nopriv_actions = array();

      /**
       * Init AJAX actions
       *
       * @author peshkov@UD
       */
      public function __construct(){

        foreach( $this->actions as $action ) {
          add_action( 'wp_ajax_' . $action, array( $this, 'request' ) );
        }

        foreach( $this->nopriv_actions as $action ) {
          add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'request' ) );
        }

      }

      /**
       * Handles AJAX request
       *
       * @author peshkov@UD
       */
      public function request() {

        $response = array(
          'message' => '',
          'html' => '',
        );

        try{

          $action = $_REQUEST[ 'action' ];

          /** Determine if the current class has the method to handle request */
          if( is_callable( array( $this, 'action_'. $action ) ) ) {
            $response = call_user_func_array( array( $this, 'action_' . $action ), array( $_REQUEST ) );
          }
          /** Determine if external function exists to handle request */
          elseif ( is_callable( 'action_' . $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          elseif ( is_callable( $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          /** Oops! */
          else {
            throw new \Exception( __( 'Incorrect Request' ) );
          }

        } catch( \Exception $e ) {
          wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( $response );

      }

      /**
       * Regenerate image sizes.
       */
      public function action_stateless_process_image() {
        @error_reporting( 0 );

        $id = (int) $_REQUEST['id'];
        $image = get_post( $id );

        if ( ! $image || 'attachment' != $image->post_type || 'image/' != substr( $image->post_mime_type, 0, 6 ) )
          throw new \Exception( sprintf( __( 'Failed resize: %s is an invalid image ID.', ud_get_stateless_media()->domain ), esc_html( $_REQUEST['id'] ) ) );

        if ( ! current_user_can( 'manage_options' ) )
          throw new \Exception( __( "Your user account doesn't have permission to resize images", ud_get_stateless_media()->domain ) );

        $fullsizepath = get_attached_file( $image->ID );

        // If no file found
        if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
          $upload_dir = wp_upload_dir();

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ), true, $fullsizepath );

          if ( $result_code !== 200 )
            throw new \Exception( sprintf( __( 'File not found (%s)', ud_get_stateless_media()->domain ), $image->guid ) );
        }

        @set_time_limit( 900 );

        $metadata = wp_generate_attachment_metadata( $image->ID, $fullsizepath );

        if ( is_wp_error( $metadata ) )
          throw new \Exception( $metadata->get_error_message() );
        if ( empty( $metadata ) )
          throw new \Exception( __( 'Unknown failure reason.', ud_get_stateless_media()->domain ) );

        // If this fails, then it just means that nothing was changed (old value == new value)
        wp_update_attachment_metadata( $image->ID, $metadata );

        return sprintf( __( '%1$s (ID %2$s) was successfully resized in %3$s seconds.', ud_get_stateless_media()->domain ), esc_html( get_the_title( $image->ID ) ), $image->ID, timer_stop() );
      }

      /**
       * @return string
       * @throws \Exception
       */
      public function action_stateless_process_file() {
        @error_reporting( 0 );

        $id = (int) $_REQUEST['id'];
        $file = get_post( $id );

        if ( ! $file || 'attachment' != $file->post_type )
          throw new \Exception( sprintf( __( 'Attachment not found: %s is an invalid file ID.', ud_get_stateless_media()->domain ), esc_html( $id ) ) );

        if ( ! current_user_can( 'manage_options' ) )
          throw new \Exception( __( "You are not allowed to do this.", ud_get_stateless_media()->domain ) );

        $fullsizepath = get_attached_file( $file->ID );

        if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
          $upload_dir = wp_upload_dir();

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ), true, $fullsizepath );

          if ( $result_code !== 200 )
            throw new \Exception( sprintf( __( 'File not found (%s)', ud_get_stateless_media()->domain ), $file->guid ) );
        } else {
          $upload_dir = wp_upload_dir();

          if ( !ud_get_stateless_media()->get_client()->media_exists( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ) ) ) {

            @set_time_limit( 900 );

            $metadata = wp_generate_attachment_metadata( $file->ID, $fullsizepath );

            if ( is_wp_error( $metadata ) )
              throw new \Exception( $metadata->get_error_message() );
            if ( empty( $metadata ) )
              throw new \Exception( __( 'Unknown failure reason.', ud_get_stateless_media()->domain ) );

            wp_update_attachment_metadata( $file->ID, $metadata );

          }

        }

        return sprintf( __( '%1$s (ID %2$s) was successfully synchronised in %3$s seconds.', ud_get_stateless_media()->domain ), esc_html( get_the_title( $file->ID ) ), $file->ID, timer_stop() );
      }

      /**
       * Returns IDs of images media objects
       */
      public function action_get_images_media_ids() {
        global $wpdb;

        if ( ! $images = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC" ) ) {
          throw new \Exception( __('No images media objects found.', ud_get_stateless_media()->domain) );
        }

        $ids = array();
        foreach ( $images as $image )
          $ids[] = (int)$image->ID;

        return $ids;
      }

      /**
       * Returns IDs of images media objects
       */
      public function action_get_other_media_ids() {
        global $wpdb;

        if ( ! $files = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%' ORDER BY ID DESC" ) ) {
          throw new \Exception( __('No files found.', ud_get_stateless_media()->domain) );
        }

        $ids = array();
        foreach ( $files as $file )
          $ids[] = (int)$file->ID;

        return $ids;
      }

    }

  }

}
