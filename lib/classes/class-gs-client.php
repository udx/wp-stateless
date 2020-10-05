<?php

/**
 * GS API Client
 *
 * @since 0.2.0
 * @author peshkov@UD
 */

namespace wpCloud\StatelessMedia {

  use wpCloud\StatelessMedia\Google_Client;
  use Google_Service_Storage;
  use WP_Error;
  use Exception;
  use Google_Service_Storage_ObjectAccessControl;
  use Google_Auth_AssertionCredentials;

  if (!class_exists('wpCloud\StatelessMedia\GS_Client')) {

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
       * @var \wpCloud\StatelessMedia\Google_Client\Google_Client $client
       */
      public $client;

      /**
       * Google Storage Service manager
       *
       * @var \Google_Service_Storage $service
       */
      public $service;

      /**
       * Google Storage Bucket
       *
       * @var
       */
      private $bucket;

      /**
       * @var
       */
      private $temp_objects = array();

      /**
       * Constructor.
       * Must not be called directly.
       *
       * @param $args
       * @author peshkov@UD
       */
      protected function __construct($args) {
        global $current_blog;
        $this->bucket = $args['bucket'];
        $this->key_json = json_decode($args['key_json'], 1);

        // May be Loading Google SDK....
        if (!class_exists('\wpCloud\StatelessMedia\Google_Client\Google_Client')) {
          include_once(ud_get_stateless_media()->path('lib/Google/vendor/autoload.php', 'dir'));
        }

        /* Initialize our client */
        $this->client = new \wpCloud\StatelessMedia\Google_Client\Google_Client();

        // We're supporting Google SDK 1.X version since
        // The plugins which also are using Google SDK may have its old version
        // what may cause conflicts
        //
        if (version_compare($this->client->getLibraryVersion(), '2.0', '<')) {
          // We should set the warning about potential issue
          // If Google SDK has different version with already included
          $this->_setWarning();

          $wp_upload_dir = wp_upload_dir();
          $dir = $wp_upload_dir['path'];
          $filename = md5(wp_generate_password()) . '.tmp';
          $path = wp_normalize_path($dir . '/' . $filename);
          @file_put_contents($path, json_encode($this->key_json));
          $cred = $this->client->loadServiceAccountJson($path, ['https://www.googleapis.com/auth/devstorage.full_control']);
          $this->client->setAssertionCredentials($cred);
          if ($this->client->getAuth()->isAccessTokenExpired()) {
            $this->client->getAuth()->refreshTokenWithAssertion($cred);
          }
          @unlink($path);
        } else {
          // May be delete warning transient if it was set
          $this->_deleteWarning();
          $this->client->setAuthConfig($this->key_json);
        }

        if (isset($current_blog) && isset($current_blog->domain)) {
          $this->client->setApplicationName($current_blog->domain);
        } else {
          $this->client->setApplicationName(urlencode(str_replace(array('http://', 'https://'), '', get_bloginfo('url'))));
        }

        $this->client->setScopes(['https://www.googleapis.com/auth/devstorage.full_control']);

        // May be Loading Google SDK. Because some bad plugins may load their Google SDK with not included Google_Service_Storage.
        if (!class_exists('Google_Service_Storage')) {
          include_once(ud_get_stateless_media()->path('lib/Google/vendor/autoload.php', 'dir'));
        }

        /* Now, Initialize our Google Storage Service */
        $this->service = new \wpCloud\StatelessMedia\Google_Client\Google_Service_Storage($this->client);
      }

      /**
       * Wrapper for listObjects()
       */
      public function list_objects($options = array()) {

        $options = wp_parse_args($options, array(
          'delimiter'  => '',
          'maxResults' => 1000,
          'pageToken'  => '',
          'prefix'     => '',
          'projection' => 'noAcl',
          'versions'   => false
        ));

        return $this->service->objects->listObjects($this->bucket, $options);
      }

      /**
       * List all items page by page of maxResults
       * @param $bucket
       * @param array $options
       * @return mixed
       */
      public function list_all_objects($options = array()) {

        $options = wp_parse_args($options, array(
          'delimiter'  => '',
          'maxResults' => 1000,
          'pageToken'  => '',
          'prefix'     => '',
          'projection' => 'noAcl',
          'versions'   => false
        ));

        $response = $this->service->objects->listObjects($this->bucket, $options);

        $this->temp_objects = array_merge($this->temp_objects, $response->getItems());

        if (!empty($response->nextPageToken)) {
          $options['pageToken'] = $response->nextPageToken;
          return $this->list_all_objects($this->bucket, $options);
        } else {
          return $this->temp_objects;
        }
      }

      /**
       * Add/Update Media Object to Bucket
       * 
       * https://stackoverflow.com/questions/26872851/resumable-uploading-to-google-cloud-storage-using-php-api
       * 
       * @author peshkov@UD
       * @param array $args
       * @return bool
       */
      public function add_media($args = array()) {
        try {

          @set_time_limit(-1);

          $args = wp_parse_args($args, array(
            'use_root' => true,
            'force' => false,
            'name' => false,
            'absolutePath' => false,
            'mimeType' => 'image/jpeg',
            'metadata' => array(),
            'is_webp' => '',
          ));

          /* Be sure file exists. */
          if (!file_exists($args['absolutePath'])) {
            return new \WP_Error('sm_error', __('Unable to locate file on disk', ud_get_stateless_media()->domain));
          }

          $object_id = isset($args['metadata']['object-id']) ? $args['metadata']['object-id'] : (isset($args['metadata']['child-of']) ? $args['metadata']['child-of'] : "");
          $object_size = isset($args['metadata']['size']) ? $args['metadata']['size'] : "";

          $args['name'] = apply_filters('wp_stateless_file_name', $args['name'], $args['use_root'], $object_id, $object_size);
          $args = apply_filters('wp_stateless_add_media_args', $args);
          $name = $args['name'];

          // If media exists we just return it
          if (!$args['force'] && $media = $this->media_exists($name)) {
            if ($media->getCacheControl() != $args['cacheControl']) {
              $media->setCacheControl($args['cacheControl']);
              $media = $this->service->objects->patch($this->bucket, $name, $media);
            }
            return get_object_vars($media);
          }

          $media = new Google_Client\Google_Service_Storage_StorageObject();
          $media->setName($name);
          $media->setMetadata($args['metadata']);

          if (isset($args['cacheControl'])) {
            $media->setCacheControl($args['cacheControl']);
          }

          if (isset($args['contentEncoding'])) {
            $media->setContentEncoding($args['contentEncoding']);
          }

          if (isset($args['contentDisposition'])) {
            $media->getContentDisposition($args['contentDisposition']);
          }

          // If chunk size is defined, we assume user needs the file to be sent by chunks
          // Otherwise, we send it directly
          if (defined('WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE') && is_int(WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE)) {
            $this->client->setDefer(true);

            $file_size = filesize($args['absolutePath']);
            $filetoupload = array('name' => $name, 'uploadType' => 'resumable');
            $request = $this->service->objects->insert($this->bucket, $media, $filetoupload);
            $uploader = new Google_Client\Google_Http_MediaFileUpload($this->client, $request, $args['mimeType'], null, true, WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE);
            $uploader->setFileSize($file_size);
            $handle = fopen($args['absolutePath'], "rb");

            $status = false;
            while (!$status && !feof($handle)) {
              $chunk = fread($handle, WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE);
              $status = $uploader->nextChunk($chunk);
            }

            $media = false;
            if ($status != false) {
              $media = $status;
            }

            fclose($handle);
            // Reset to the client to execute requests immediately in the future.
            $this->client->setDefer(false);
          } else {
            $media = $this->service->objects->insert($this->bucket, $media, array_filter(array(
              'data' => file_get_contents($args['absolutePath']),
              'uploadType' => 'media',
              'mimeType' => $args['mimeType'],
              'predefinedAcl' => 'bucketOwnerFullControl',
            )));
          }

          $this->mediaInsertACL($name, $media, $args);
        } catch (Exception $e) {
          return new WP_Error('sm_error', $e->getMessage());
        }
        return get_object_vars($media);
      }

      /**
       * Update Object ACL
       */
      public function mediaInsertACL($name, $media = array(), $agrs = array()) {
        /* Make Media Public READ for all on success */
        if (!empty($name)) {
          $acl = new \wpCloud\StatelessMedia\Google_Client\Google_Service_Storage_ObjectAccessControl();
          $acl->setEntity('allUsers');
          $acl->setRole('READER');
          $acl = apply_filters('wp_stateless_media_acl', $acl, $name, $media, $agrs);
          $this->service->objectAccessControls->insert($this->bucket, $name, $acl);
        }
      }

      /**
       * Get or save media file
       * 
       * @param $path
       * @param bool $save
       * @param bool $save_path
       * @return bool|\Google_Service_Storage_StorageObject|int
       */
      public function get_media($path, $save = false, $save_path = false) {
        try {
          $media = $this->service->objects->get($this->bucket, $path);
        } catch (\Exception $e) {
          return false;
        }

        if (empty($media->id)) return false;

        if ($save && $save_path) {
          if (!file_exists($_dir = dirname($save_path))) {
            wp_mkdir_p($_dir);
          }
          return $this->client->getHttpClient()->get($media->getMediaLink(), ['save_to' => $save_path])->getStatusCode();
        }

        return $media;
      }

      /**
       * get or save media file
       * @param $path
       * @param bool $save
       * @param bool $save_path
       * @return bool|\Google_Service_Storage_StorageObject|int
       */
      public function copy_media($path, $new_path) {
        try {
          $media = $this->service->objects->get($this->bucket, $path);
          $media = $this->service->objects->copy($this->bucket, $path, $this->bucket, $new_path, $media);
          $this->mediaInsertACL($new_path, $media);
        } catch (\Exception $e) {
          return false;
        }

        if (empty($media->id)) return false;

        return $media;
      }

      /**
       * get or save media file
       * @param $path
       * @param bool $save
       * @param bool $save_path
       * @return bool|\Google_Service_Storage_StorageObject|int
       */
      public function move_media($path, $new_path) {
        try {
          $media = $this->copy_media($path, $new_path);
          $this->remove_media($path);
        } catch (\Exception $e) {
          return false;
        }

        if (empty($media->id)) return false;

        return $media;
      }

      /**
       * Check if media exists
       * @param $path
       * @return bool|object
       */
      public function media_exists($path) {
        try {
          $media = $this->service->objects->get($this->bucket, $path);
          // Here we wanted to check if access allowed, but noticed it actually sets this ACL... Leaving it as is. @author korotkov@ud
          $this->service->objectAccessControls->get($this->bucket, $path, 'allUsers');
        } catch (\Exception $e) {
          return false;
        }

        if (empty($media->id)) return false;
        return $media;
      }

      /**
       * Fired for every file remove action
       *
       * @author peshkov@UD
       * @param string $name
       * @param string $id
       * @param boolean $use_root
       * @param string $size
       * @param boolean $is_webp
       * @return bool
       */
      public function remove_media($name, $id = "", $use_root = true, $size = "", $is_webp = false) {
        try {
          $name = apply_filters('wp_stateless_file_name', $name, $use_root, $id, $size);
          if ($is_webp && substr($name, -4) !=  "webp") $name .= ".webp";

          $this->service->objects->delete($this->bucket, $name);
        } catch (Exception $e) {
          return new WP_Error('sm_error', $e->getMessage());
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
          $this->service->buckets->get($this->bucket);
        } catch (Exception $e) {
          return $e;
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
      public static function get_instance($args) {
        if (null === self::$instance) {

          try {

            if (empty($args['bucket'])) {
              throw new Exception(__('<b>Bucket</b> parameter must be provided.'));
            }

            $json = "{}";

            if (!empty($args['key_json'])) {
              $json = json_decode($args['key_json']);
            }

            if (!$json || !property_exists($json, 'private_key')) {
              throw new Exception(__('<b>Service Account JSON</b> is invalid.'));
            }

            self::$instance = new self($args);
          } catch (Exception $e) {
            return new WP_Error('sm_error', $e->getMessage());
          }
        }
        return self::$instance;
      }

      /**
       * Set warning about potential conflict with Google SDK
       *
       * @since 2.0.1
       */
      private function _setWarning() {

        $reflector = new \ReflectionClass('Google_Client');
        $pluginBasename = wp_normalize_path(plugin_basename($reflector->getFileName()));

        // Check if get_plugins() function exists. This is required on the front end of the
        // site, since it is in a file that is normally only loaded in the admin.
        if (!function_exists('get_plugins')) {
          require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $pluginBasenameParts = explode('/', $pluginBasename);
        $pluginName = __("UNDEFINED", ud_get_stateless_media()->domain);

        foreach (get_plugins() as $path => $meta) {
          if (strpos($path, trailingslashit($pluginBasenameParts[0])) === 0) {
            $pluginName = $meta['Name'];
          }
        };

        $error = sprintf(
          __("%s plugin may have potential Google SDK version conflicts with %s plugin. %s is using Google SDK %s, when %s loads old Google SDK version %s.", ud_get_stateless_media()->domain),
          "<b>" . 'WP-Stateless' . "</b>",
          "<b>" . $pluginName . "</b>",
          'WP-Stateless',
          "<b>v2.0</b>",
          $pluginName,
          "<b>v" . \wpCloud\StatelessMedia\Google_Client\Google_Client::LIBVER . "</b>"
        );

        set_transient("wp_stateless_google_sdk_conflict", $error);
      }

      /**
       * Removes Warning if it exists
       *
       */
      private function _deleteWarning() {
        delete_transient("wp_stateless_google_sdk_conflict");
      }
    }
  }
}
