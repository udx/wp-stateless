<?php
/**
 * GS API Client
 *
 * @since 0.2.0
 * @author peshkov@UD
 */
namespace wpCloud\StatelessMedia {

  use Google_Client;
  use Google_Service_Storage;
  use WP_Error;
  use Exception;
  use Google_Service_Storage_ObjectAccessControl;
  use Google_Auth_AssertionCredentials;

  if( !class_exists( 'wpCloud\StatelessMedia\GS_Client' ) ) {

    final class GS_Client {

      /**
       * Singleton object
       *
       * @var \wpCloud\StatelessMedia\GS_Client
       */
      private static $instance;

      /**
       * Google Client manager
       *
       * @var \Google_Client $client
       */
      public $client;

      /**
       * Google Storage Service manager
       *
       * @var \Google_Service_Storage $service
       */
      public $service;

      /**
       * Email Address
       *
       * @var string
       */
      private $service_account_name;

      /**
       * Absolute path to p12 file
       *
       * @var
       */
      private $key_file_path;

      /**
       * Google Storage Bucket
       *
       * @var
       */
      private $bucket;

      /**
       * Constructor.
       * Must not be called directly.
       *
       * @param $args
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        global $current_blog;
        $this->service_account_name = $args[ 'service_account_name' ];
        $this->key_file_path = $args[ 'key_file_path' ];
        $this->bucket = $args[ 'bucket' ];

        /* Initialize our client */
        $this->client = new Google_Client();

        if( isset( $current_blog ) && isset( $current_blog->domain ) ) {
          $this->client->setApplicationName( $current_blog->domain );
        } else {
          $this->client->setApplicationName( urlencode( str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) ) ) );
        }

        /**
         * If we have an access token, we can carry on.
         * Otherwise, we'll get one with the help of an
         * assertion credential. We also supply
         * the service account
         */
        if ( get_transient( 'wp-stateless-media:service_token' ) ) {
          $this->client->setAccessToken( get_transient( 'wp-stateless-media:service_token' ) );
        }

        /* We check the file in get_instance() !  */
        $key = file_get_contents( $this->key_file_path );

        $cred = new Google_Auth_AssertionCredentials( $this->service_account_name, array( 'https://www.googleapis.com/auth/devstorage.full_control' ), $key );

        $this->client->setAssertionCredentials( $cred );

        if( $this->client->getAuth()->isAccessTokenExpired() ) {
          $this->client->getAuth()->refreshTokenWithAssertion( $cred );
          set_transient( 'wp-stateless-media:service_token', $this->client->getAccessToken(), 86400 );
        }

        /* Now, Initialize our Google Storage Service */
        $this->service = new Google_Service_Storage( $this->client );
      }
      
      /**
       * Add/Update Media Object to Bucket
       *
       * @author peshkov@UD
       * @param array $args
       * @return bool
       */
      public function add_media( $args = array() ) {
        try {

          extract( $args = wp_parse_args( $args, array(
            'name' => false,
            'absolutePath' => false,
            'mimeType' => 'image/jpeg',
            'metadata' => array(),
          ) ) );

          /* Be sure file exists. */
          if( !file_exists( $args['absolutePath'] ) ) {
            return new \WP_Error( 'sm_error', __( 'Unable to locate file on disk', ud_get_stateless_media()->domain ) );
          }

          /* Set default name if parameter was not passed. */
          if( empty( $name ) ) {
            $name = basename( $args['name'] );
          }

          $media = new \Google_Service_Storage_StorageObject();
          $media->setName( $name );
          $media->setMetadata( $args['metadata'] );

          if( isset( $args['cacheControl'] ) ) {
            $media->setCacheControl( $args['cacheControl'] );
          }

          if( isset( $args['contentEncoding'] ) ) {
            $media->setContentEncoding( $args['contentEncoding'] );
          }

          if( isset( $args['contentDisposition'] ) ) {
            $media->getContentDisposition( $args['contentDisposition'] );
          }

          /* Upload Media file to Google storage */
          $media = $this->service->objects->insert( $this->bucket, $media, array_filter( array(
            'data' => file_get_contents( $args['absolutePath'] ),
            'uploadType' => 'media',
            'mimeType' => $args['mimeType'],
            'predefinedAcl' => 'bucketOwnerFullControl',
          ) ));

          /* Make Media Public READ for all on success */
          if( is_object( $media ) ) {
            $acl = new Google_Service_Storage_ObjectAccessControl();
            $acl->setEntity( 'allUsers' );
            $acl->setRole( 'READER' );

            $this->service->objectAccessControls->insert( $this->bucket, $name, $acl );
          }

        } catch( Exception $e ) {
          return new WP_Error( 'sm_error', $e->getMessage() );
        }
        return get_object_vars( $media );
      }
      
      /**
       * Fired for every file remove action
       *
       * @author peshkov@UD
       * @param string $name
       * @return bool
       */
      public function remove_media( $name ) {
        try {
          $this->service->objects->delete( $this->bucket, $name );
        } catch( Exception $e ) {
          return new WP_Error( 'sm_error', $e->getMessage() );
        }
        return true;
      }

      /**
       * Tests connection to Google Storage
       * by trying to get passed bucket's data.
       *
       * @author peshkov@UD
       */
      public function is_connected() {
        try {
          $bucket = $this->service->buckets->get( $this->bucket );
        } catch( Exception $e ) {
          return false;
        }
        return true;
      }

      /**
       * Determine if instance already exists and Return Instance
       *
       * @param array $args
       *
       * $args
       * @param string client_id
       * @param string service_account_name
       * @param string key_file_path
       *
       * @author peshkov@UD
       * @return \wpCloud\StatelessMedia\GS_Client
       */
      public static function get_instance( $args ) {

        if( null === self::$instance ) {

          try {
            if( empty( $args[ 'service_account_name' ] ) ) {
              throw new Exception( __( '<b>Email Address</b> parameter must be provided.' ) );
            }

            if( empty( $args[ 'bucket' ] ) ) {
              throw new Exception( __( '<b>Bucket</b> parameter must be provided.' ) );
            }

            if( empty( $args[ 'key_file_path' ] ) || !file_exists( $args[ 'key_file_path' ] ) ) {
              throw new Exception( __( '<b>Key File Path</b> parameter is not provided or <b>p12</b> file does not exist.' ) );
            }

            self::$instance = new self( $args );
          } catch( Exception $e ) {
            return new WP_Error( 'sm_error', $e->getMessage() );
          }
        }
        return self::$instance;
      }

    }

  }

}
