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
        'get_non_library_files_id',
        'stateless_process_file',
        'stateless_process_non_library_file',
        'stateless_get_current_progresses',
        'stateless_wizard_update_settings',
        'stateless_reset_progress',
        'stateless_get_all_fails'
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
       * Update json key to database.
       */
      public function action_stateless_wizard_update_settings($data) {
        $bucket = $data['bucket'];
        $privateKeyData = base64_decode($data['privateKeyData']);

        if(is_network_admin()){
          if(get_site_option('sm_mode', 'disabled') == 'disabled')
            update_site_option( 'sm_mode', 'cdn');
          update_site_option( 'sm_bucket', $bucket);
          update_site_option( 'sm_key_json', $privateKeyData);
        }
        else{
          if(get_option('sm_mode', 'disabled') == 'disabled')
            update_option( 'sm_mode', 'cdn');
          update_option( 'sm_bucket', $bucket);
          update_option( 'sm_key_json', $privateKeyData);
        }

        ud_get_stateless_media()->flush_transients();
        wp_send_json(array('success' => true));
      }

      /**
       * Fail over to image URL if not found on disk
       * In case image not available on both local and bucket
       * try to pull image from image URL in case it is accessible by some sort of proxy.
       * 
       * @param:
       * $url (int/string): URL of the image.
       * $save_to (string): Path where to save the image.
       * 
       * @return:
       * boolean (true/false)
       * 
       */
      public function get_attachment_if_exist($url, $save_to){
        if(is_int($url))
          $url = wp_get_attachment_url($url);

        $response = wp_remote_get( $url );
        if ( !is_wp_error($response) && is_array( $response ) ) {
          if(!empty($response['response']['code']) && $response['response']['code'] == 200){
            try{
              if(wp_mkdir_p(dirname($save_to))){
                return file_put_contents($save_to, $response['body']);
              }
            }
            catch(Exception $e){
              throw $e;
            }
          }
        }
        return false;
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
          $result_code = ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath )), true, $fullsizepath );

          if ( $result_code !== 200 ) {
            if(!$this->get_attachment_if_exist($image->ID, $fullsizepath)){ // Save file to local from proxy.
              $this->store_failed_attachment( $image->ID, 'images' );
              throw new \Exception(sprintf(__('Both local and remote files are missing. Unable to resize. (%s)', ud_get_stateless_media()->domain), $image->guid));
            }
          }
        }

        @set_time_limit( -1 );

        $metadata = wp_generate_attachment_metadata( $image->ID, $fullsizepath );

        if ( is_wp_error( $metadata ) ) {
          $this->store_failed_attachment( $image->ID, 'images' );
          throw new \Exception($metadata->get_error_message());
        }
        if ( empty( $metadata ) ) {
          $this->store_failed_attachment( $image->ID, 'images' );
          throw new \Exception(__('Unknown failure reason.', ud_get_stateless_media()->domain));
        }

        // If this fails, then it just means that nothing was changed (old value == new value)
        wp_update_attachment_metadata( $image->ID, $metadata );

        $this->store_current_progress( 'images', $id );
        $this->maybe_fix_failed_attachment( 'images', $image->ID );

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
        $local_file_exists = file_exists( $fullsizepath );

        if ( false === $fullsizepath || ! $local_file_exists ) {
          $upload_dir = wp_upload_dir();

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ), true, $fullsizepath );

          if ( $result_code !== 200 ) {
            if(!$this->get_attachment_if_exist($file->ID, $fullsizepath)){ // Save file to local from proxy.
              $this->store_failed_attachment( $file->ID, 'other' );
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
          $upload_dir = wp_upload_dir();

          if ( !ud_get_stateless_media()->get_client()->media_exists( str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $fullsizepath ) ) ) {

            @set_time_limit( -1 );

            $metadata = wp_generate_attachment_metadata( $file->ID, $fullsizepath );

            if ( is_wp_error( $metadata ) ) {
              $this->store_failed_attachment( $file->ID, 'other' );
              throw new \Exception($metadata->get_error_message());
            }
            if ( empty( $metadata ) ) {
              $this->store_failed_attachment( $file->ID, 'other' );
              throw new \Exception(__('Unknown failure reason.', ud_get_stateless_media()->domain));
            }

            wp_update_attachment_metadata( $file->ID, $metadata );

          }
          else{
            // Stateless mode: we don't need the local version.
            if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
              unlink($fullsizepath);
            }
          }

        }

        $this->store_current_progress( 'other', $id );
        $this->maybe_fix_failed_attachment( 'other', $file->ID );

        return sprintf( __( '%1$s (ID %2$s) was successfully synchronised in %3$s seconds.', ud_get_stateless_media()->domain ), esc_html( get_the_title( $file->ID ) ), $file->ID, timer_stop() );
      }

      /**
       * @return string
       * @throws \Exception
       */
      public function action_stateless_process_non_library_file() {
        @error_reporting( 0 );
        $upload_dir = wp_upload_dir();
        $client = ud_get_stateless_media()->get_client();

        $file_path = trim($_REQUEST['file_path'], '/');
        $fullsizepath = $upload_dir['basedir'] . '/' . $file_path;

        if ( ! current_user_can( 'manage_options' ) )
          throw new \Exception( __( "You are not allowed to do this.", ud_get_stateless_media()->domain ) );

        $local_file_exists = file_exists( $fullsizepath );

        if ( !$local_file_exists ) {

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media( $fullsizepath, true, $fullsizepath );

          if ( $result_code !== 200 ) {
            // if(!$this->get_attachment_if_exist($file->ID, $fullsizepath)){ // Save file to local from proxy.
              $this->store_failed_attachment( $file->ID, 'other' );
              throw new \Exception(sprintf(__('File not found (%s)', ud_get_stateless_media()->domain), $file->guid));
            // }
            // else{
              // $local_file_exists = true;
            // }
          }
          else{
            $local_file_exists = true;
          }
        }

        if($local_file_exists){

          if ( !ud_get_stateless_media()->get_client()->media_exists( $file_path )) {

            @set_time_limit( -1 );
            $file_type = wp_check_filetype($fullsizepath);
            /* Add 'image size' image */
            $media = $client->add_media( array(
              'name' => $file_path,
              'absolutePath' => $fullsizepath,
              'cacheControl' => apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', $fullsizepath),
              'contentDisposition' => apply_filters( 'sm:item:contentDisposition', null, $fullsizepath),
              'mimeType' => $file_type['type'],
              'metadata' => array(
                'child-of' => dirname($file_path),
                'file-hash' => md5( $file_path ),
              ),
            ));



          }
          else{
            // Stateless mode: we don't need the local version.
            if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
              unlink($fullsizepath);
            }
          }

        }

        $this->store_current_progress( 'other', $id );
        $this->maybe_fix_failed_attachment( 'other', $file->ID );

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

        $continue = false;
        if ( isset( $_REQUEST['continue'] ) ) {
          $continue = (bool) $_REQUEST['continue'];
        }

        return $this->get_non_processed_media_ids( 'images', $images, $continue );
      }

      /**
       * Returns IDs of images media objects
       */
      public function action_get_other_media_ids() {
        global $wpdb;

        if ( ! $files = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type NOT LIKE 'image/%' ORDER BY ID DESC" ) ) {
          throw new \Exception( __('No files found.', ud_get_stateless_media()->domain) );
        }

        $continue = false;
        if ( isset( $_REQUEST['continue'] ) ) {
          $continue = (bool) $_REQUEST['continue'];
        }

        return $this->get_non_processed_media_ids( 'other', $files, $continue );
      }

      /**
       * Returns IDs of non media library files
       */
      public function action_get_non_library_files_id() {
        $files = apply_filters( 'sm:sync::nonMediaFiles', array() );
        return $files;
      }

      /**
       * Returns current progress storage for all modes (to check whether there is something to continue in JS)
       */
      public function action_stateless_get_current_progresses() {
        return array(
          'images'  => $this->retrieve_current_progress( 'images' ),
          'other'   => $this->retrieve_current_progress( 'other' ),
        );
      }

      /**
       * @return array
       */
      public function action_stateless_get_all_fails() {
        return array(
          'images' => $this->get_fails( 'images' ),
          'other'  => $this->get_fails( 'other' )
        );
      }

      /**
       * @param $mode
       */
      private function get_fails( $mode ) {
        if ( $mode !== 'other' ) {
          $mode = 'images';
        }

        return get_option( 'wp_stateless_failed_' . $mode );
      }

      /**
       * Resets the current progress for a specific mode.
       */
      public function action_stateless_reset_progress() {
        $mode = 'images';
        if ( isset( $_REQUEST['mode'] ) && 'other' === $_REQUEST['mode'] ) {
          $mode = 'other';
        }

        $this->reset_current_progress( $mode );

        return true;
      }

      /**
       * @param $mode
       * @param $files
       * @param bool $continue
       * @return array
       */
      private function get_non_processed_media_ids( $mode, $files, $continue = false ) {
        if ( $continue ) {
          $progress = $this->retrieve_current_progress( $mode );
          if ( false !== $progress ) {
            $ids = array();
            foreach ( $files as $file ) {
              $id = (int) $file->ID;
              // only include IDs that have not been processed yet
              if ( $id > $progress[0] || $id < $progress[1] ) {
                $ids[] = $id;
              }
            }
            return $ids;
          }
        }

        $this->reset_current_progress( $mode );

        $ids = array();
        foreach ( $files as $file )
          $ids[] = (int)$file->ID;

        return $ids;
      }

      /**
       * @param $mode
       * @param $id
       */
      private function store_current_progress( $mode, $id ) {
        if ( $mode !== 'other' ) {
          $mode = 'images';
        }

        $first_processed = get_option( 'wp_stateless_' . $mode . '_first_processed' );
        if ( ! $first_processed ) {
          update_option( 'wp_stateless_' . $mode . '_first_processed', $id );
        }
        $last_processed = get_option( 'wp_stateless_' . $mode . '_last_processed' );
        if ( ! $last_processed || $id < (int) $last_processed ) {
          update_option( 'wp_stateless_' . $mode . '_last_processed', $id );
        }
      }

      /**
       * @param $attachment_id
       * @param $mode
       */
      private function store_failed_attachment( $attachment_id, $mode ) {
        if ( $mode !== 'other' ) {
          $mode = 'images';
        }

        $fails = get_option( 'wp_stateless_failed_' . $mode );
        if ( !empty( $fails ) && is_array( $fails ) ) {
          if ( !in_array( $attachment_id, $fails ) ) {
            $fails[] = $attachment_id;
          }
        } else {
          $fails = array( $attachment_id );
        }

        update_option( 'wp_stateless_failed_' . $mode, $fails );
      }

      /**
       * @param $mode
       * @param $attachment_id
       */
      private function maybe_fix_failed_attachment( $mode, $attachment_id ) {
        $fails = get_option( 'wp_stateless_failed_' . $mode );

        if ( !empty( $fails ) && is_array( $fails ) ) {
          if ( in_array( $attachment_id, $fails ) ) {
            foreach (array_keys($fails, $attachment_id) as $key) {
              unset($fails[$key]);
            }
          }
        }

        update_option( 'wp_stateless_failed_' . $mode, $fails );
      }

      /**
       * @param $mode
       * @return array|bool
       */
      private function retrieve_current_progress( $mode ) {
        if ( $mode !== 'other' ) {
          $mode = 'images';
        }

        $first_processed = get_option( 'wp_stateless_' . $mode . '_first_processed' );
        $last_processed = get_option( 'wp_stateless_' . $mode . '_last_processed' );

        if ( ! $first_processed || ! $last_processed ) {
          return false;
        }

        return array( (int) $first_processed, (int) $last_processed );
      }

      /**
       * @param $mode
       */
      private function reset_current_progress( $mode ) {
        if ( $mode !== 'other' ) {
          $mode = 'images';
        }

        delete_option( 'wp_stateless_' . $mode . '_first_processed' );
        delete_option( 'wp_stateless_' . $mode . '_last_processed' );
      }

    }

  }

}
