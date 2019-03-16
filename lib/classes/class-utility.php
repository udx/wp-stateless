<?php
/**
 * Helper Functions List
 *
 * Can be called via Singleton. Since Singleton uses magic method __call().
 * Example:
 *
 * Add Media to GS storage:
 * ud_get_stateless_media()->add_media( false, $post_id );
 *
 * @class Utility
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Utility' ) ) {

    class Utility {

      /**
       * ChromeLogger
       *
       * @author potanin@UD
       * @param $data
       */
      static public function log( $data ) {

        if( !class_exists( 'wpCloud\StatelessMedia\Logger' )) {
          include_once( __DIR__ . '/class-logger.php' );
        }

        if( !class_exists( 'wpCloud\StatelessMedia\Logger' )) {
          return;
        }

        if( defined( 'WP_STATELESS_CONSOLE_LOG' ) && WP_STATELESS_CONSOLE_LOG ) {
          Logger::log( '[wp-stateless]', $data );
        }

      }

      /**
       * Override Cache Control
       * @param $cacheControl
       * @return mixed
       */
      public static function override_cache_control( $cacheControl ) {
        return ud_get_stateless_media()->get( 'sm.cache_control' );
      }

      /**
       * wp_normalize_path was added in 3.9.0
       *
       * @param $path
       * @return mixed|string
       *
       */
      public static function normalize_path( $path ) {

        if( function_exists( 'wp_normalize_path' ) ) {
          return wp_normalize_path( $path );
        }

        $path = str_replace( '\\', '/', $path );
        $path = preg_replace( '|/+|','/', $path );
        return $path;

      }

      /**
       * Randomize file name
       * @param $filename
       * @return string
       */
      public static function randomize_filename( $filename ) {
        $return = apply_filters('stateless_skip_cache_busting', null, $filename);
        if($return){
          return $return;
        }
        $info = pathinfo($filename);
        $ext = empty($info['extension']) ? '' : '' . $info['extension'];
        $_parts = array();
        $rand = substr(md5(time()), 0, 8);

        $body_rewrite_types = ud_get_stateless_media()->get( 'sm.body_rewrite_types' );
        if(empty($info['extension']) || strpos($body_rewrite_types, $info['extension']) === false){
          return $filename;
        }

        if(strpos($filename, $rand) !== false){
          return $filename;
        }

        if (strpos($info['filename'], '@')) {
          $_cleanName = explode('@', $info['filename'])[0];
          $_retna = explode('@', $info['filename'])[1];
          $_parts[] = $rand;
          $_parts[] = '-';
          $_parts[] = strtolower($_cleanName);
          $_parts[] = '@' . strtolower($_retna);
        } else {
          $_parts[] = $rand;
          $_parts[] = '-';
          $_parts[] = strtolower($info['filename']);
        }

        $filename = join('', $_parts);
        if(!empty($ext)){
          $filename .= '.' . $ext;
        }
        
        return $filename;
      }

      /**
       * Get Media Item Content Disposition
       *
       * @param null $attachment_id
       * @param array $metadata
       * @param array $data
       * @return string
       */
      public static function getContentDisposition( $attachment_id = null, $metadata = array(), $data = array() ) {
        // return 'Content-Disposition: attachment; filename=some-file.sql';

        return apply_filters( 'sm:item:contentDisposition', null, array( 'attachment_id' => $attachment_id, 'mime_type' => get_post_mime_type( $attachment_id ), 'metadata' => $metadata, 'data' => $data ) );

      }

      /**
       * @param null $attachment_id
       * @param array $metadata
       * @param array $data
       * @return string
       */
      public static function getCacheControl( $attachment_id = null, $metadata = array(), $data = array() ) {

        if( !$attachment_id ) {
          return apply_filters( 'sm:item:cacheControl', 'private, no-cache, no-store', $attachment_id, array( 'attachment_id' => null, 'mime_type' => null, 'metadata' => $metadata, 'data' => $data ) );
        }

        $_mime_type = get_post_mime_type( $attachment_id );

        // Treat images as public.
        if( strpos( $_mime_type, 'image/' ) !== false ) {
          return apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', array( 'attachment_id' => $attachment_id, 'mime_type' => null, 'metadata' => $metadata, 'data' => $data ) );
        }

        // Treat images as public.
        if( strpos( $_mime_type, 'sql' ) !== false ) {
          return apply_filters( 'sm:item:cacheControl', 'private, no-cache, no-store', array( 'attachment_id' => $attachment_id, 'mime_type' => null, 'metadata' => $metadata, 'data' => $data ) );
        }

        return apply_filters( 'sm:item:cacheControl', 'public, max-age=30, no-store, must-revalidate', array( 'attachment_id' => $attachment_id, 'mime_type' => null, 'metadata' => $metadata, 'data' => $data ) );

      }

      /**
       * Add/Update Media to Bucket
       * Fired for every action with image add or update
       * 
       * $force and $args params will no be passed on media library uploads.
       * This two will be passed on by compatibility.
       *
       * @action wp_generate_attachment_metadata
       * @author peshkov@UD
       * @param $metadata
       * @param $attachment_id
       * @param $force Whether to force the upload incase of it's already exists.
       * @param $args Whether to only sync the full size image.
       * @return bool|string
       */
      public static function add_media( $metadata, $attachment_id, $force = false, $args = array() ) {
        global $stateless_synced_full_size;
        $file = '';
        $upload_dir = wp_upload_dir();
        $args = wp_parse_args($args, array(
          'no_thumb' => false,
        ));

        /* Get metadata in case if method is called directly. */
        if( current_filter() !== 'wp_generate_attachment_metadata' && current_filter() !== 'wp_update_attachment_metadata' ) {
          $metadata = wp_get_attachment_metadata( $attachment_id );
        }
        
        /**
         * To skip the sync process.
         *
         * Returning a non-null value
         * will effectively short-circuit the function.
         *
         * $force and $args params will no be passed on non media library uploads.
         * This two will be passed on by compatibility.
         * 
         * @since 2.2.4
         *
         * @param bool              $value          This should return true if want to skip the sync.
         * @param int               $metadata       Metadata for the attachment.
         * @param string            $attachment_id  Attachment ID.
         * @param bool              $force          (optional) Whether to force the sync even the file already exist in GCS.
         * @param bool              $args           (optional) Whether to only sync the full size image.
         */
        $check = apply_filters('wp_stateless_skip_add_media', null, $metadata, $attachment_id, $force, $args);

        $client = ud_get_stateless_media()->get_client();

        if( !is_wp_error( $client ) && !$check ) {

          $fullsizepath = wp_normalize_path( get_attached_file( $attachment_id ) );
          // Make non-images uploadable.
          if( empty( $metadata['file'] ) && $attachment_id ) {
            $file = str_replace( wp_normalize_path(trailingslashit( $upload_dir[ 'basedir' ] )), '', $fullsizepath );
            if(empty($metadata)){
              $metadata = array();
            }
            $mime_type = get_post_mime_type( $attachment_id );
            if($mime_type != "application/pdf"){
              $metadata["file"] = $file;
            }
          }

          $file = wp_normalize_path( !empty($metadata[ 'file' ])?$metadata[ 'file' ]:$file );

          $image_host = ud_get_stateless_media()->get_gs_host();
          $bucketLink = apply_filters('wp_stateless_bucket_link', $image_host);

          $_metadata = array(
            "width" => isset( $metadata[ 'width' ] ) ? $metadata[ 'width' ] : null,
            "height" => isset( $metadata[ 'height' ] )  ? $metadata[ 'height' ] : null,
            'object-id' => $attachment_id,
            'source-id' => md5( $attachment_id.ud_get_stateless_media()->get( 'sm.bucket' ) ),
            'file-hash' => md5( $file )
          );

          if($attachment_id && !empty($metadata) && !$force){
            $db_metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
            if($db_metadata != $metadata){
              $force = true;
            }
          }

          /* Add default image */
          $media = $client->add_media( $_mediaOptions = array_filter( array(
            'force' => $force && $stateless_synced_full_size != $attachment_id,
            'name' => $file,
            'absolutePath' => wp_normalize_path( get_attached_file( $attachment_id ) ),
            'cacheControl' => $_cacheControl = self::getCacheControl( $attachment_id, $metadata, null ),
            'contentDisposition' => $_contentDisposition = self::getContentDisposition( $attachment_id, $metadata, null ),
            'mimeType' => get_post_mime_type( $attachment_id ),
            'metadata' => $_metadata
          ) ));

          // Break if we have errors.
          // @note Errors could be due to key being invalid or now having sufficient permissions in which case should notify user.
          if( is_wp_error( $media ) ) {
            return $metadata;
          }

          /* Add Google Storage metadata to our attachment */
          $fileLink = $bucketLink . '/' . ( !empty($media['name']) ? $media['name'] : $file );

          $cloud_meta = array(
            'id' => $media[ 'id' ],
            'name' => !empty($media['name']) ? $media['name'] : $file,
            'fileLink' => $fileLink,
            'storageClass' => $media[ 'storageClass' ],
            'mediaLink' => $media[ 'mediaLink' ],
            'selfLink' => $media[ 'selfLink' ],
            'bucket' => ud_get_stateless_media()->get( 'sm.bucket' ),
            'object' => $media,
            'sizes' => array(),
          );

          if( isset( $_cacheControl ) && $_cacheControl ) {
            //update_post_meta( $attachment_id, 'sm_cloud:cacheControl', $_cacheControl );
            $cloud_meta[ 'cacheControl' ] = $_cacheControl;
          }

          if( isset( $_contentDisposition ) && $_contentDisposition ) {
            //update_post_meta( $attachment_id, 'sm_cloud:contentDisposition', $_contentDisposition );
            $cloud_meta[ 'contentDisposition' ] = $_contentDisposition;
          }

          if( empty( $metadata[ 'sizes' ] ) ) {
            // @note This could happen if WordPress does not have any wp_get_image_editor(), e.g. Imagemagic not installed.
          }

          /* Now we go through all available image sizes and upload them to Google Storage */
          if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) && $args['no_thumb'] != true ) {

            $path = wp_normalize_path( dirname( get_attached_file( $attachment_id ) ) );
            $mediaPath = wp_normalize_path( trim( dirname( $file ), '\/\\' ) );

            /**
             * @see https://github.com/wpCloud/wp-stateless/issues/343
             **/
            $mediaPath = $mediaPath === '.' ? '' : $mediaPath;

            foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {

              $absolutePath = wp_normalize_path( $path . '/' . $data[ 'file' ] );

              /* Add 'image size' image */
              $media = $client->add_media( array(
                'force' => $force,
                'name' => $file_path = trim($mediaPath . '/' . $data[ 'file' ], '/'),
                'absolutePath' => $absolutePath,
                'cacheControl' => $_cacheControl,
                'contentDisposition' => $_contentDisposition,
                'mimeType' => $data[ 'mime-type' ],
                'metadata' => array_merge( $_metadata, array(
                  'width' => $data['width'],
                  'height' => $data['height'],
                  'child-of' => $attachment_id,
                  'file-hash' => md5( $data[ 'file' ] )
                ))
              ));

              /* Break if we have errors. */
              if( !is_wp_error( $media ) ) {

                $fileLink = $bucketLink . '/' . (!empty($media['name']) ? $media['name'] : $file_path);

                // @note We don't add storageClass because it's same as parent...
                $cloud_meta[ 'sizes' ][ $image_size ] = array(
                  'id' => $mediaPath . '/' . $media[ 'id' ],
                  'name' => !empty($media['name']) ? $media['name'] : $file_path,
                  'fileLink' => $fileLink,
                  'mediaLink' => $media[ 'mediaLink' ],
                  'selfLink' => $media[ 'selfLink' ]
                );
                
                // Stateless mode: we don't need the local version.
                if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                  unlink($absolutePath);
                }
              }


            }

          }

          // Stateless mode: we don't need the local version.
          if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless' && $args['no_thumb'] != true){
            unlink($fullsizepath);
          }

          update_post_meta( $attachment_id, 'sm_cloud', $cloud_meta );

          if($args['no_thumb'] == true){
            $stateless_synced_full_size = $attachment_id;
          }

          /**
          * Triggers when the media and it's childs are synced.
          *
          * $force and $args params will no be passed on non media library uploads.
          * This two will be passed on by compatibility.
          * 
          * @since 2.2.5
          *
          * @param int               $metadata       Metadata for the attachment.
          * @param string            $attachment_id  Attachment ID.
          * @param bool              $force          (optional) Whether to force the sync even the file already exist in GCS.
          * @param bool              $args           (optional) Whether to only sync the full size image.
          */
          do_action( 'wp_stateless_media_synced', $metadata, $attachment_id, $force, $args);
        }

        return $metadata;
      }

      /**
       * Remove Media from Bucket by post ID
       * Fired on calling function wp_delete_attachment()
       *
       * @todo: add error logging. peshkov@UD
       * @see wp_delete_attachment()
       * @action delete_attachment
       * @author peshkov@UD
       * @param $post_id
       */
      public static function remove_media( $post_id ) {
        /* Get attachments metadata */
        $metadata = wp_get_attachment_metadata( $post_id );

        /* Be sure we have the same bucket in settings and have GS object's name before proceed. */
        if(
          isset( $metadata[ 'gs_name' ] ) &&
          isset( $metadata[ 'gs_bucket' ] ) &&
          $metadata[ 'gs_bucket' ] == ud_get_stateless_media()->get( 'sm.bucket' )
        ) {

          $client = ud_get_stateless_media()->get_client();
          if( !is_wp_error( $client ) ) {

            /* Remove default image */
            $client->remove_media( $metadata[ 'gs_name' ] );

            /* Now, go through all sizes and remove 'image sizes' images from Bucket too. */
            if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {
              foreach( $metadata[ 'sizes' ] as $k => $v ) {
                if( !empty( $v[ 'gs_name' ] ) ) {
                  $client->remove_media( $v[ 'gs_name' ] );
                }
              }
            }

          }

        }

      }

    }

  }

}
