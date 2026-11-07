<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Client')) {
  require_once dirname(__FILE__) . '/class-google-rest-client.php';
}

if (!class_exists('wpCloud\StatelessMedia\GS_Buckets')) {
  final class GS_Buckets {
    private $client;

    public function __construct(Google_REST_Client $client) {
      $this->client = $client;
    }

    public function get($bucket) {
      $info = $this->client->get_bucket($bucket);
      return json_decode(wp_json_encode($info), false, 512, JSON_THROW_ON_ERROR);
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\GS_Service')) {
  final class GS_Service {
    public $buckets;

    public function __construct(Google_REST_Client $client) {
      $this->buckets = new GS_Buckets($client);
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\GS_Client')) {
  final class GS_Client {
    private static $instance;
    public $client;
    public $service;
    private $bucket;
    private $temp_objects = array();
    private $key_json = '';

    protected function __construct($args) {
      $this->bucket = $args['bucket'];
      $this->key_json = json_decode($args['key_json'], true);
      $this->client = new Google_REST_Client($this->bucket, $this->key_json);
      $this->service = new GS_Service($this->client);
      delete_transient('wp_stateless_google_sdk_conflict');
    }

    public function list_objects($options = array()) {
      $options = wp_parse_args($options, array(
        'delimiter' => '',
        'maxResults' => 1000,
        'pageToken' => '',
        'prefix' => '',
        'projection' => 'noAcl',
        'versions' => false,
      ));
      $options = array_filter($options, function ($value, $key) {
        return $value !== '' && $value !== false && $value !== null;
      }, ARRAY_FILTER_USE_BOTH);
      return new Google_REST_Object_List($this->client->list_objects($options));
    }

    public function list_all_objects($options = array()) {
      $options = wp_parse_args($options, array(
        'delimiter' => '',
        'maxResults' => 1000,
        'pageToken' => '',
        'prefix' => '',
        'projection' => 'noAcl',
        'versions' => false,
      ));
      $this->temp_objects = array();
      do {
        $response = $this->list_objects($options);
        $this->temp_objects = array_merge($this->temp_objects, $response->getItems());
        $options['pageToken'] = $response->nextPageToken;
      } while (!empty($response->nextPageToken));
      return $this->temp_objects;
    }

    public function add_media($args = array()) {
      try {
        set_time_limit(0);
        $args = wp_parse_args($args, array(
          'use_root' => true,
          'force' => false,
          'name' => false,
          'absolutePath' => false,
          'mimeType' => 'image/jpeg',
          'metadata' => array(),
          'cacheControl' => null,
          'contentEncoding' => null,
          'contentDisposition' => null,
          'is_webp' => '',
          'skipLocalCheck' => false,
        ));
        if (!$args['skipLocalCheck'] && !file_exists($args['absolutePath'])) {
          return new \WP_Error('sm_error', __('Unable to locate file on disk', ud_get_stateless_media()->domain));
        }
        $object_id = isset($args['metadata']['object-id']) ? $args['metadata']['object-id'] : (isset($args['metadata']['child-of']) ? $args['metadata']['child-of'] : '');
        $object_size = isset($args['metadata']['size']) ? $args['metadata']['size'] : '';
        $args['name'] = apply_filters('wp_stateless_file_name', $args['name'], $args['use_root'], $object_id, $object_size);
        $args = apply_filters('wp_stateless_add_media_args', $args);
        $name = $args['name'];

        if (!$args['force'] && ($media = $this->media_exists($name))) {
          if ($args['cacheControl'] !== null && $media->getCacheControl() !== $args['cacheControl']) {
            return $this->client->patch_object($name, array('cacheControl' => $args['cacheControl']));
          }
          return $media->to_array();
        }

        $metadata = array('name' => $name, 'metadata' => $args['metadata']);
        foreach (array('cacheControl', 'contentEncoding', 'contentDisposition') as $field) {
          if ($args[$field] !== null && $args[$field] !== '') $metadata[$field] = $args[$field];
        }
        $chunk_size = defined('WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE') && is_int(WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE)
          ? WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE : Google_REST_Client::DEFAULT_CHUNK_SIZE;
        $manages_acl = $this->manages_object_acl();
        $predefined_acl = $manages_acl ? 'bucketOwnerFullControl' : '';
        $media = $this->client->upload($name, $args['absolutePath'], $metadata, $args['mimeType'], $predefined_acl, $chunk_size);
        if ($manages_acl) {
          $this->mediaInsertACL($name, $media, $args);
        }
        return $media;
      } catch (\Throwable $e) {
        return new \WP_Error('sm_error', $e->getMessage());
      }
    }

    public function update_media($name, $args) {
      $options = array();
      if (isset($args['predefinedAcl']) && $this->manages_object_acl()) {
        $options['predefinedAcl'] = $args['predefinedAcl'];
      }
      unset($args['predefinedAcl']);
      $allowed = array('metadata', 'cacheControl', 'contentDisposition', 'contentEncoding', 'contentType', 'storageClass');
      $fields = array_intersect_key($args, array_flip($allowed));
      return $this->client->patch_object($name, $fields, $options);
    }

    private function manages_object_acl() {
      if (defined('WP_STATELESS_SKIP_ACL_SET') && WP_STATELESS_SKIP_ACL_SET) return false;
      try {
        return !$this->client->has_uniform_access();
      } catch (\Throwable $e) {
        return true;
      }
    }

    public function mediaInsertACL($name, $media = array(), $agrs = array()) {
      if (empty($name)) return;
      $acl = new Google_REST_Object(array('entity' => 'allUsers', 'role' => 'READER'));
      $acl = apply_filters('wp_stateless_media_acl', $acl, $name, $media, $agrs);
      $data = is_object($acl) && is_callable(array($acl, 'to_array')) ? $acl->to_array() : get_object_vars($acl);
      return $this->client->insert_object_acl($name, $data);
    }

    public function get_media($path, $save = false, $save_path = false) {
      try {
        $media = new Google_REST_Object($this->client->get_object($path));
      } catch (\Throwable $e) {
        return false;
      }
      if (empty($media->id)) return false;
      if ($save && $save_path) {
        try {
          return $this->client->download($path, $save_path);
        } catch (\Throwable $e) {
          return false;
        }
      }
      return $media;
    }

    public function copy_media($path, $new_path) {
      try {
        $source = $this->client->get_object($path);
        $fields = array_intersect_key($source, array_flip(array('cacheControl', 'contentDisposition', 'contentEncoding', 'contentType', 'metadata', 'storageClass', 'customTime', 'eventBasedHold', 'temporaryHold', 'retentionExpirationTime')));
        $media = new Google_REST_Object($this->client->copy_object($path, $new_path, $fields));
        if (empty($media->id)) return false;
        if ($this->manages_object_acl()) {
          $this->mediaInsertACL($new_path, $media);
        }
        return $media;
      } catch (\Throwable $e) {
        return false;
      }
    }

    public function move_media($path, $new_path) {
      $media = $this->copy_media($path, $new_path);
      if (!$media || empty($media->id)) return false;
      $removed = $this->remove_media($path);
      if (is_wp_error($removed)) return $removed;
      return $media;
    }

    public function media_exists($path) {
      try {
        $media = new Google_REST_Object($this->client->get_object($path));
      } catch (\Throwable $e) {
        return false;
      }
      return empty($media->id) ? false : $media;
    }

    public function remove_media($name, $id = '', $use_root = true, $size = '', $is_webp = false) {
      try {
        $name = apply_filters('wp_stateless_file_name', $name, $use_root, $id, $size);
        if ($is_webp && substr($name, -4) !== 'webp') $name .= '.webp';
        $this->client->delete_object($name);
      } catch (\Throwable $e) {
        return new \WP_Error('sm_error', $e->getMessage());
      }
      return true;
    }

    public function is_connected() {
      try {
        $this->client->get_bucket();
      } catch (\Throwable $e) {
        return $e;
      }
      return true;
    }

    public static function get_instance($args) {
      if (null === self::$instance) {
        try {
          if (empty($args['bucket'])) {
            throw new \Exception(__('<b>Bucket</b> parameter must be provided.'));
          }
          $json = !empty($args['key_json']) ? json_decode($args['key_json'], true) : array();
          if (!is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
            throw new \Exception(__('<b>Service Account JSON</b> is invalid.'));
          }
          self::$instance = new self($args);
        } catch (\Throwable $e) {
          return new \WP_Error('sm_error', $e->getMessage());
        }
      }
      return self::$instance;
    }
  }
}
