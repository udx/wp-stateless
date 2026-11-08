<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Exception')) {
  /**
   * Describes a Google API or transport failure without exposing credentials.
   */
  class Google_REST_Exception extends \Exception {
    private $status = 0;
    private $response = null;
    private $reason = '';

    /**
     * @param string $message Sanitized failure message.
     * @param int $status HTTP status, or zero for a local transport failure.
     * @param array|string|null $response Parsed Google error response, when available.
     */
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

    /** @return int HTTP status, or zero for a local failure. */
    public function getStatus() {
      return $this->status;
    }

    /** @return array|string|null Parsed error response, when available. */
    public function getResponse() {
      return $this->response;
    }

    /** @return array Legacy error list containing reason and message. */
    public function getErrors() {
      return array(array('reason' => $this->reason, 'message' => $this->getMessage()));
    }

    /** @return array Legacy handler context containing the sanitized message. */
    public function getHandlerContext() {
      return array('error' => $this->getMessage());
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Object')) {
  /**
   * Wraps Google object metadata with the legacy property and ArrayAccess API.
   */
  class Google_REST_Object implements \ArrayAccess {
    private $data = array();

    /** @param mixed $data Raw object metadata; non-array input is normalized to an empty array. */
    public function __construct($data = array()) {
      $this->data = is_array($data) ? $data : array();
    }

    /** @return mixed Field value, or null when the field is absent. */
    public function __get($name) {
      return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /** @return bool Whether the metadata field exists and is not null. */
    public function __isset($name) {
      return isset($this->data[$name]);
    }

    /** @return array Raw Google object metadata. */
    public function to_array() {
      return $this->data;
    }

    /** @return string|null Object Cache-Control header. */
    public function getCacheControl() {
      return $this->__get('cacheControl');
    }

    /**
     * @param string $value Cache-Control header value.
     * @return Google_REST_Object This object for chaining.
     */
    public function setCacheControl($value) {
      $this->data['cacheControl'] = $value;
      return $this;
    }

    /**
     * @param string $value ACL entity, such as `allUsers`.
     * @return Google_REST_Object This object for chaining.
     */
    public function setEntity($value) {
      $this->data['entity'] = $value;
      return $this;
    }

    /**
     * @param string $value ACL role, such as `READER`.
     * @return Google_REST_Object This object for chaining.
     */
    public function setRole($value) {
      $this->data['role'] = $value;
      return $this;
    }

    /** @return string|null Authenticated media download URL. */
    public function getMediaLink() {
      return $this->__get('mediaLink');
    }

    /** @return array<string,mixed>|null Custom object metadata. */
    public function getMetadata() {
      return $this->__get('metadata');
    }

    /**
     * @param array<string,mixed> $value Custom object metadata.
     * @return Google_REST_Object This object for chaining.
     */
    public function setMetadata($value) {
      $this->data['metadata'] = $value;
      return $this;
    }

    /** @return string|null Exact object name. */
    public function getName() {
      return $this->__get('name');
    }

    /** @return string|null Object size in bytes, represented as a string by Google. */
    public function getSize() {
      return $this->__get('size');
    }

    /** @return string|null Google object ID. */
    public function getId() {
      return $this->__get('id');
    }

    /**
     * @param mixed $offset Metadata field name.
     * @return bool Whether the field exists and is not null.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) {
      return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset Metadata field name.
     * @return mixed Field value, or null when absent.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
      return $this->__get($offset);
    }

    /**
     * @param mixed $offset Metadata field name.
     * @param mixed $value New field value.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
      $this->data[$offset] = $value;
    }

    /** @param mixed $offset Metadata field name to remove. */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset) {
      unset($this->data[$offset]);
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Object_List')) {
  /**
   * Represents one page of Google object-list results.
   */
  class Google_REST_Object_List {
    /** @var Google_REST_Object[] Objects in this page. */
    public $items = array();

    /** @var string|null Continuation token for the next page. */
    public $nextPageToken = null;

    /** @var string[] Prefixes returned when a delimiter is used. */
    public $prefixes = array();

    /** @param array $data Raw Google object-list response. */
    public function __construct($data = array()) {
      $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
      $this->items = array_map(function ($item) {
        return new Google_REST_Object($item);
      }, $items);
      $this->nextPageToken = isset($data['nextPageToken']) ? $data['nextPageToken'] : null;
      $this->prefixes = isset($data['prefixes']) && is_array($data['prefixes']) ? $data['prefixes'] : array();
    }

    /** @return Google_REST_Object[] Objects in this page. */
    public function getItems() {
      return $this->items;
    }

    /** @return string[] Prefixes returned when a delimiter is used. */
    public function getPrefixes() {
      return $this->prefixes;
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Google_REST_Client')) {
  /**
   * Minimal Google Cloud Storage REST transport using WordPress HTTP functions.
   *
   * Owns service-account JWT bearer tokens, retries, resumable uploads, and
   * streamed downloads. It intentionally implements only the operations used by
   * WP-Stateless rather than the complete Google SDK surface.
   */
  class Google_REST_Client {
    const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    const API_ROOT = 'https://storage.googleapis.com/storage/v1';
    const UPLOAD_ROOT = 'https://storage.googleapis.com/upload/storage/v1';
    const DEFAULT_CHUNK_SIZE = 8388608;

    private $bucket;
    private $key;
    private $access_token = '';
    private $access_token_expires = 0;
    private $uniform_access = null;

    /**
     * @param mixed $bucket Bucket name used for object operations; validated at runtime.
     * @param mixed $key Service-account data containing `client_email` and `private_key`; validated at runtime.
     * @throws Google_REST_Exception When configuration is invalid or OpenSSL is unavailable.
     */
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

    /**
     * Reports whether the configured bucket uses uniform bucket-level access.
     *
     * The result is cached for the lifetime of this client.
     *
     * @return bool True when per-object ACLs are not used.
     */
    public function has_uniform_access() {
      if ($this->uniform_access === null) {
        $bucket = $this->get_bucket();
        $this->uniform_access = !empty($bucket['iamConfiguration']['uniformBucketLevelAccess']['enabled']);
      }
      return $this->uniform_access;
    }

    /**
     * Retrieves bucket metadata.
     *
     * @param string|null $bucket Optional bucket override; defaults to the configured bucket.
     * @return array Raw bucket metadata.
     */
    public function get_bucket($bucket = null) {
      $bucket = $bucket === null ? $this->bucket : $bucket;
      return $this->request('GET', self::API_ROOT . '/b/' . rawurlencode($bucket));
    }

    /**
     * Retrieves one object's metadata by its exact name.
     *
     * @param string $name Exact object name.
     * @param array $options Optional query parameters, such as `generation`.
     * @return array Raw object metadata.
     */
    public function get_object($name, $options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('GET', $url);
    }

    /**
     * Lists one page of objects.
     *
     * @param array $options Query parameters including `prefix`, `maxResults`, and `pageToken`.
     * @return array Raw object-list response.
     */
    public function list_objects($options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o';
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('GET', $url);
    }

    /**
     * Updates object metadata without replacing the object body.
     *
     * @param string $name Exact object name.
     * @param array $fields Metadata fields to patch.
     * @param array $options Optional query parameters, such as `predefinedAcl`.
     * @return array Raw object metadata.
     */
    public function patch_object($name, $fields, $options = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      if ($options) $url .= '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
      return $this->request('PATCH', $url, array('json' => $fields));
    }

    /**
     * Copies an object within the configured bucket.
     *
     * @param string $name Source object name.
     * @param string $new_name Destination object name.
     * @param array $fields Metadata to preserve or override on the destination.
     * @return array Raw destination object metadata.
     */
    public function copy_object($name, $new_name, $fields = array()) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name)
        . '/copyTo/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($new_name);
      $fields['bucket'] = $this->bucket;
      $fields['name'] = $new_name;
      return $this->request('POST', $url, array('json' => $fields));
    }

    /**
     * Deletes one exact object name.
     *
     * @param string $name Exact object name.
     * @return array Empty response on success.
     */
    public function delete_object($name) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name);
      return $this->request('DELETE', $url, array('expected' => array(200, 204)));
    }

    /**
     * Creates an ACL entry for an object.
     *
     * @param string $name Exact object name.
     * @param array $acl ACL resource containing `entity` and `role`.
     * @return array Raw ACL response.
     */
    public function insert_object_acl($name, $acl) {
      $url = self::API_ROOT . '/b/' . rawurlencode($this->bucket) . '/o/' . rawurlencode($name) . '/acl';
      return $this->request('POST', $url, array('json' => $acl));
    }

    /**
     * Streams an object to disk and atomically moves it into place.
     *
     * A unique temporary file is removed on failure. No object content is loaded
     * into memory as a single string.
     *
     * @param string $name Exact object name.
     * @param string $save_path Final local destination.
     * @return int HTTP 200 on success.
     * @throws Google_REST_Exception When the directory, download, or final rename fails.
     */
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

    /**
     * Uploads a file in bounded chunks using a resumable session.
     *
     * Zero-byte files are supported. A 308 response advances using Google's
     * Range header when present, or the last acknowledged chunk otherwise.
     *
     * @param string $name Exact object name.
     * @param string $path Readable local source file.
     * @param array $metadata Object resource metadata, including `name` and custom `metadata`.
     * @param string $mime_type Object content type.
     * @param string $predefined_acl Optional predefined ACL; empty for uniform access.
     * @param int $chunk_size Chunk size in bytes, at least 262144 when positive.
     * @return array Raw object metadata for the completed upload.
     * @throws Google_REST_Exception When the file, session, or a chunk fails.
     */
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

    /**
     * Returns a cached bearer token or signs a new service-account assertion.
     *
     * Tokens refresh one minute before their nominal expiry, including between
     * chunks of a long resumable upload.
     *
     * @return string OAuth access token.
     * @throws Google_REST_Exception When signing or token exchange fails.
     */
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

    /**
     * Executes an authenticated request through WordPress's HTTP API.
     *
     * Arguments include `headers`, `body`, `json`, `timeout`, `retries`,
     * `expected`, `return_response`, `stream`, and `filename`. Redirects are
     * disabled so credentials cannot be forwarded to another host.
     *
     * @param string $method HTTP method.
     * @param string $url Complete request URL.
     * @param array $args Request options and expected response behavior.
     * @return array Decoded response data, or the raw WordPress HTTP response array when requested.
     * @throws Google_REST_Exception After transport or unexpected HTTP responses are exhausted.
     */
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

    /**
     * Decodes an HTTP response as JSON when possible.
     *
     * @param array $response WordPress HTTP response array.
     * @return array|string Decoded array, raw non-JSON body, or an empty array for an empty body.
     */
    private function response_data($response) {
      $raw = wp_remote_retrieve_body($response);
      if ($raw === '') return array();
      $data = json_decode($raw, true);
      return is_array($data) ? $data : $raw;
    }

    /**
     * Extracts a bounded error message without request credentials.
     *
     * @param array|string $data Parsed response body.
     * @param int $code HTTP status code.
     * @return string Sanitized error message, limited to 500 characters for raw bodies.
     */
    private function error_message($data, $code) {
      if (is_array($data)) {
        if (!empty($data['error']['message'])) return $data['error']['message'];
        if (!empty($data['error_description'])) return $data['error_description'];
        if (!empty($data['error']) && is_string($data['error'])) return $data['error'];
      }
      if (is_string($data) && $data !== '') return substr($data, 0, 500);
      return sprintf(__('Google API request failed with HTTP %d.', ud_get_stateless_media()->domain), $code);
    }

    /**
     * Encodes JWT segments using unpadded base64url.
     *
     * @param string $value Raw JSON segment or signature bytes.
     * @return string Base64url-encoded value.
     */
    private function base64url($value) {
      return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
  }
}
