<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Exception')) {
  class Google_REST_Exception extends \Exception {
    private $status = 0;
    private $response = null;
    private $reason = '';

    public function __construct($message, $status = 0, $response = null) {
      $this->status = (int) $status;
      $this->response = $response;
      if (is_array($response) && isset($response['error']['errors'][0]['reason'])) {
        $this->reason = $response['error']['errors'][0]['reason'];
      } elseif (is_array($response) && isset($response['error']['status'])) {
        $this->reason = strtolower($response['error']['status']);
      }
      parent::__construct($message, $this->status);
    }

    public function getStatus() {
      return $this->status;
    }

    public function getResponse() {
      return $this->response;
    }

    public function getErrors() {
      return array(array('reason' => $this->reason, 'message' => $this->getMessage()));
    }

    public function getHandlerContext() {
      return array('error' => $this->getMessage());
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Object')) {
  class Google_REST_Object implements \ArrayAccess {
    private $data = array();

    public function __construct($data = array()) {
      $this->data = is_array($data) ? $data : array();
    }

    public function __get($name) {
      return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __isset($name) {
      return isset($this->data[$name]);
    }

    public function to_array() {
      return $this->data;
    }

    public function getCacheControl() {
      return $this->__get('cacheControl');
    }

    public function setCacheControl($value) {
      $this->data['cacheControl'] = $value;
      return $this;
    }

    public function setEntity($value) {
      $this->data['entity'] = $value;
      return $this;
    }

    public function setRole($value) {
      $this->data['role'] = $value;
      return $this;
    }

    public function getMediaLink() {
      return $this->__get('mediaLink');
    }

    public function getMetadata() {
      return $this->__get('metadata');
    }

    public function setMetadata($value) {
      $this->data['metadata'] = $value;
      return $this;
    }

    public function getName() {
      return $this->__get('name');
    }

    public function getSize() {
      return $this->__get('size');
    }

    public function getId() {
      return $this->__get('id');
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
      return isset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
      return $this->__get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
      $this->data[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
      unset($this->data[$offset]);
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Object_List')) {
  class Google_REST_Object_List {
    public $items = array();
    public $nextPageToken = null;
    public $prefixes = array();

    public function __construct($data = array()) {
      $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
      $this->items = array_map(function ($item) {
        return new Google_REST_Object($item);
      }, $items);
      $this->nextPageToken = isset($data['nextPageToken']) ? $data['nextPageToken'] : null;
      $this->prefixes = isset($data['prefixes']) && is_array($data['prefixes']) ? $data['prefixes'] : array();
    }

    public function getItems() {
      return $this->items;
    }

    public function getPrefixes() {
      return $this->prefixes;
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Client')) {
  class Google_REST_Client {
    const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    const API_ROOT = 'https://storage.googleapis.com/storage/v1';
    const UPLOAD_ROOT = 'https://storage.googleapis.com/upload/storage/v1';
    const DEFAULT_CHUNK_SIZE = 8388608;

    private $bucket;
    private $key;
    private $access_token = '';
    private $access_token_expires = 0;

    public function __construct($bucket, $key) {
      if (!is_string($bucket) || trim($bucket) === '') {
        throw new Google_REST_Exception(__('Bucket parameter must be provided.', ud_get_stateless_media()->domain));
      }
      if (!is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
        throw new Google_REST_Exception(__('Service Account JSON is invalid.', ud_get_stateless_media()->domain));
      }
      if (!function_exists('openssl_sign')) {
        throw new Google_REST_Exception(__('The OpenSSL PHP extension is required to sign Google service-account tokens.', ud_get_stateless_media()->domain));
      }
      $this->bucket = trim($bucket);
      $this->key = $key;
    }

    public function get_bucket() {
      return $this->request('GET', self::API_ROOT . '/b/' . rawurlencode($this->bucket));
    }

    public function get_object($name, $options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('GET', $url);
    }

    public function list_objects($options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o';
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('GET', $url);
    }

    public function patch_object($name, $fields, $options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('PATCH', $url, array('json' => $fields));
    }

    public function copy_object($name, $new_name, $fields = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name)
        . '/copyTo/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($new_name);
      $fields['bucket'] = $this->bucket;
      $fields['name'] = $new_name;
      return $this->request('POST', $url, array('json' => $fields));
    }

    public function delete_object($name) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      return $this->request('DELETE', $url, array('expected' => array(200, 204)));
    }

    public function insert_object_acl($name, $acl) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name) . '/acl';
      return $this->request('POST', $url, array('json' => $acl));
    }

    public function download($name, $save_path) {
      $directory = dirname($save_path);
      if (!is_dir($directory) && !wp_mkdir_p($directory)) {
        throw new Google_REST_Exception(sprintf(__('Unable to create the download directory: %s', ud_get_stateless_media()->domain), $directory));
      }
      $temporary = $save_path . '.wp-stateless-' . wp_generate_password(8, false, false) . '.tmp';
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name) . '?alt=media';
      try {
        $response = $this->request('GET', $url, array(
          'expected' => array(200),
          'return_response' => true,
          'stream' => true,
          'filename' => $temporary,
          'timeout' => 300,
        ));
        $status = (int) wp_remote_retrieve_response_code($response);
        if (!@rename($temporary, $save_path)) {
          throw new Google_REST_Exception(sprintf(__('Unable to move the downloaded file into place: %s', ud_get_stateless_media()->domain), $save_path));
        }
        @chmod($save_path, 0644);
        return $status;
      } catch (\Throwable $e) {
        if (is_file($temporary)) @unlink($temporary);
        throw $e;
      }
    }

    public function upload($name, $path, $metadata, $mime_type, $predefined_acl = '', $chunk_size = self::DEFAULT_CHUNK_SIZE) {
      if (!is_file($path) || !is_readable($path)) {
        throw new Google_REST_Exception(__('Unable to locate file on disk', ud_get_stateless_media()->domain));
      }
      $size = filesize($path);
      if ($size === false) {
        throw new Google_REST_Exception(sprintf(__('Unable to determine the upload size: %s', ud_get_stateless_media()->domain), $path));
      }
      $chunk_size = (int) $chunk_size;
      if ($chunk_size < 262144) $chunk_size = self::DEFAULT_CHUNK_SIZE;
      $handle = fopen($path, 'rb');
      if ($handle === false) {
        throw new Google_REST_Exception(sprintf(__('Unable to open the upload: %s', ud_get_stateless_media()->domain), $path));
      }

      $query = array('uploadType' => 'resumable');
      if ($predefined_acl !== '') $query['predefinedAcl'] = $predefined_acl;
      $url = self::UPLOAD_ROOT . '/b/' . rawurlencode($this->bucket) . '/o?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
      $mime_type = $mime_type ? $mime_type : 'application/octet-stream';
      try {
        $session = $this->request('POST', $url, array(
          'json' => $metadata,
          'headers' => array(
            'X-Upload-Content-Type' => $mime_type,
            'X-Upload-Content-Length' => (string) $size,
          ),
          'expected' => array(200),
          'return_response' => true,
          'timeout' => 60,
        ));
        $upload_url = wp_remote_retrieve_header($session, 'location');
        if (!$upload_url) {
          throw new Google_REST_Exception(__('Google did not return a resumable upload session.', ud_get_stateless_media()->domain));
        }

        $offset = 0;
        $result = array();
        while ($offset < $size || $size === 0) {
          $length = $size === 0 ? 0 : min($chunk_size, $size - $offset);
          $chunk = $length ? fread($handle, $length) : '';
          if ($chunk === false || ($length && strlen($chunk) !== $length)) {
            throw new Google_REST_Exception(sprintf(__('Unable to read the complete upload chunk at byte %d.', ud_get_stateless_media()->domain), $offset));
          }
          $last = $offset + $length;
          $headers = array(
            'Content-Type' => $mime_type,
            'Content-Length' => (string) $length,
          );
          $headers['Content-Range'] = $size === 0 ? 'bytes */0' : sprintf('bytes %d-%d/%d', $offset, $last - 1, $size);
          $response = $this->request('PUT', $upload_url, array(
            'body' => $chunk,
            'headers' => $headers,
            'expected' => array(200, 201, 308),
            'return_response' => true,
            'timeout' => 300,
            'retries' => 3,
          ));
          $code = (int) wp_remote_retrieve_response_code($response);
          if ($code === 308) {
            $range = wp_remote_retrieve_header($response, 'range');
            if (preg_match('/^bytes=0-(\d+)$/', $range, $matches)) {
              $offset = (int) $matches[1] + 1;
              if ($offset !== $last) fseek($handle, $offset);
            } else {
              $offset = $last;
            }
            continue;
          }
          $offset = $last;
          $result = $this->response_data($response);
          break;
        }
        fclose($handle);
        return $result;
      } catch (\Throwable $e) {
        fclose($handle);
        throw $e;
      }
    }

    private function access_token() {
      if ($this->access_token !== '' && $this->access_token_expires > time() + 60) {
        return $this->access_token;
      }
      $now = time();
      $header = $this->base64url(wp_json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
      $claims = $this->base64url(wp_json_encode(array(
        'iss' => $this->key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/devstorage.full_control',
        'aud' => self::TOKEN_ENDPOINT,
        'iat' => $now,
        'exp' => $now + 3600,
      )));
      $unsigned = $header . '.' . $claims;
      $private_key = openssl_pkey_get_private($this->key['private_key']);
      if ($private_key === false || !openssl_sign($unsigned, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
        throw new Google_REST_Exception(__('Unable to sign the Google service-account token with the configured private key.', ud_get_stateless_media()->domain));
      }
      $assertion = $unsigned . '.' . $this->base64url($signature);
      $response = wp_remote_post(self::TOKEN_ENDPOINT, array(
        'timeout' => 30,
        'redirection' => 0,
        'body' => array(
          'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
          'assertion' => $assertion,
        ),
      ));
      if (is_wp_error($response)) {
        throw new Google_REST_Exception($response->get_error_message());
      }
      $code = (int) wp_remote_retrieve_response_code($response);
      $data = $this->response_data($response);
      if ($code !== 200 || empty($data['access_token'])) {
        throw new Google_REST_Exception($this->error_message($data, $code), $code, $data);
      }
      $this->access_token = $data['access_token'];
      $this->access_token_expires = time() + (isset($data['expires_in']) ? (int) $data['expires_in'] : 3600);
      return $this->access_token;
    }

    public function request($method, $url, $args = array()) {
      $args = wp_parse_args($args, array(
        'headers' => array(),
        'body' => null,
        'json' => null,
        'timeout' => 60,
        'retries' => 2,
        'expected' => array(200, 201, 202, 204),
        'return_response' => false,
        'stream' => false,
        'filename' => '',
      ));
      $headers = array_merge(array(
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $this->access_token(),
        'User-Agent' => 'WP-Stateless/4.6.0',
      ), $args['headers']);
      $body = $args['body'];
      if ($args['json'] !== null) {
        $body = wp_json_encode($args['json']);
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
      }
      $request = array(
        'method' => $method,
        'timeout' => $args['timeout'],
        'redirection' => 0,
        'headers' => $headers,
        'body' => $body,
        'stream' => $args['stream'],
      );
      if ($args['filename'] !== '') $request['filename'] = $args['filename'];

      $attempt = 0;
      while (true) {
        $response = wp_remote_request($url, $request);
        if (is_wp_error($response)) {
          if ($attempt < $args['retries']) {
            $attempt++;
            sleep($attempt);
            continue;
          }
          throw new Google_REST_Exception($response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if (in_array($code, $args['expected'], true)) {
          return $args['return_response'] ? $response : $this->response_data($response);
        }
        if ($attempt < $args['retries'] && ($code === 429 || $code >= 500)) {
          $attempt++;
          sleep($attempt * 2);
          continue;
        }
        $data = $this->response_data($response);
        throw new Google_REST_Exception($this->error_message($data, $code), $code, $data);
      }
    }

    private function response_data($response) {
      $raw = wp_remote_retrieve_body($response);
      if ($raw === '') return array();
      $data = json_decode($raw, true);
      return is_array($data) ? $data : $raw;
    }

    private function error_message($data, $code) {
      if (is_array($data)) {
        if (!empty($data['error']['message'])) return $data['error']['message'];
        if (!empty($data['error_description'])) return $data['error_description'];
        if (!empty($data['error']) && is_string($data['error'])) return $data['error'];
      }
      if (is_string($data) && $data !== '') return substr($data, 0, 500);
      return sprintf(__('Google API request failed with HTTP %d.', ud_get_stateless_media()->domain), $code);
    }

    private function base64url($value) {
      return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
  }
}
