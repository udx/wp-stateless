<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\StreamWrapper')) {
  /**
   * Implements `gs://` streams using exact object names and local temp files.
   *
   * Unlike media uploads, stream paths never pass through root-directory or
   * filename filters. That separation prevents recursion through wp_upload_dir()
   * and accidental stripping of an existing object prefix.
   */
  class StreamWrapper {
    const DEFAULT_PROTOCOL = 'gs';
    const FILE_WRITABLE_MODE = 33206;
    const FILE_READABLE_MODE = 33060;
    const DIRECTORY_WRITABLE_MODE = 16895;
    const DIRECTORY_READABLE_MODE = 16676;

    public $context;
    private static $clients = array();
    private $client;
    private $protocol;
    private $bucket;
    private $file = '';
    private $stream;
    private $temporary = '';
    private $dirty = false;
    private $directory = array();
    private $directory_position = 0;

    /** Flushes any unclosed temporary stream before releasing the wrapper. */
    public function __destruct() {
      $this->stream_close();
    }

    /**
     * Registers a client for a stream protocol, replacing an existing wrapper.
     *
     * @param GS_Client|\WP_Error $client Storage client used by all streams for this protocol.
     * @param string|null $protocol Protocol name; defaults to `gs`.
     * @return bool True when the wrapper is registered, false for an invalid client.
     */
    public static function register($client, $protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      if (is_wp_error($client)) return false;
      if (in_array($protocol, stream_get_wrappers(), true)) {
        stream_wrapper_unregister($protocol);
      }
      self::$clients[$protocol] = $client;
      return stream_wrapper_register($protocol, StreamWrapper::class, STREAM_IS_URL);
    }

    /**
     * Unregisters a stream protocol and clears its stored client.
     *
     * @param string|null $protocol Protocol name; defaults to `gs`.
     */
    public static function unregister($protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      if (in_array($protocol, stream_get_wrappers(), true)) stream_wrapper_unregister($protocol);
      unset(self::$clients[$protocol]);
    }

    /**
     * Returns the client registered for a protocol.
     *
     * @param string|null $protocol Protocol name; defaults to `gs`.
     * @return GS_Client|null Registered client, or null when none is configured.
     */
    public static function getClient($protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      return isset(self::$clients[$protocol]) ? self::$clients[$protocol] : null;
    }

    /**
     * Parses a stream URL and selects its exact bucket and object name.
     *
     * @param string $path Complete `gs://bucket/object` URL.
     * @return GS_Client Client for the current stream context.
     * @throws \RuntimeException For an invalid URL or a bucket outside the configured client.
     */
    private function open_path($path) {
      $url = (array) parse_url($path) + array('scheme' => '', 'path' => '', 'host' => '');
      if ($url['scheme'] === '' || $url['host'] === '') throw new \RuntimeException('Invalid Google Storage path.');
      $this->protocol = $url['scheme'];
      $this->bucket = $url['host'];
      $this->file = ltrim($url['path'], '/');
      $this->client = self::getClient($this->protocol);
      if (!$this->client) throw new \RuntimeException('No Google Storage client is registered for ' . $this->protocol . '://');
      if ($this->bucket !== ud_get_stateless_media()->get('sm.bucket')) {
        throw new \RuntimeException('The stream client is configured for a different bucket.');
      }
      return $this->client;
    }

    /**
     * Allocates a local temporary file for a read/write stream.
     *
     * @return string Temporary file path.
     * @throws \RuntimeException When WordPress cannot allocate the file.
     */
    private function temporary_file() {
      $path = wp_tempnam('wp-stateless-stream');
      if (!$path) throw new \RuntimeException('Unable to allocate a temporary stream file.');
      $this->temporary = $path;
      return $path;
    }

    /**
     * Emits a warning when requested and returns false for a stream callback.
     *
     * @param string $message Sanitized error message.
     * @param int $flags Stream operation flags.
     * @return bool Always false.
     */
    private function return_error($message, $flags) {
      if ($flags & STREAM_REPORT_ERRORS) trigger_error($message, E_USER_WARNING);
      return false;
    }

    /**
     * Opens an object for reading, writing, or appending through a local stream.
     *
     * Supports `r`, `w`, and `a` modes with their binary/text variants.
     *
     * @param string $path Complete stream URL.
     * @param string $mode PHP stream mode.
     * @param int $flags Stream operation flags.
     * @param string|null $opened_path Populated when STREAM_USE_PATH is requested.
     * @return bool True when the local stream opens successfully.
     */
    public function stream_open($path, $mode, $flags, &$opened_path) {
      try {
        $this->open_path($path);
        $mode = rtrim($mode, 'bt');
        $temporary = $this->temporary_file();
        if ($mode === 'r') {
          if ($this->client->get_media($this->file, true, $temporary) !== 200) {
            throw new \RuntimeException('Object not found: ' . $this->file);
          }
          $mode = 'rb';
        } elseif ($mode === 'w') {
          $mode = 'w+b';
        } elseif ($mode === 'a') {
          if ($this->client->media_exists($this->file)) {
            if ($this->client->get_media($this->file, true, $temporary) !== 200) {
              throw new \RuntimeException('Unable to download object for append: ' . $this->file);
            }
          }
          $mode = 'a+b';
        } else {
          throw new \RuntimeException('Unknown stream_open mode.');
        }
        $this->stream = fopen($temporary, $mode);
        if ($this->stream === false) throw new \RuntimeException('Unable to open temporary stream file.');
        if ($flags & STREAM_USE_PATH) $opened_path = $path;
        return true;
      } catch (\Throwable $e) {
        if ($this->temporary && is_file($this->temporary)) @unlink($this->temporary);
        $this->temporary = '';
        return $this->return_error($e->getMessage(), $flags);
      }
    }

    /** @return string|false Bytes read from the local stream, or false when closed. */
    public function stream_read($count) {
      return $this->stream ? fread($this->stream, $count) : false;
    }

    /**
     * Writes bytes to the local stream and marks it for upload on flush/close.
     *
     * @param string $data Bytes to write.
     * @return int|false Number of bytes written, or false when closed.
     */
    public function stream_write($data) {
      if (!$this->stream) return false;
      $written = fwrite($this->stream, $data);
      if ($written) $this->dirty = true;
      return $written;
    }

    /** @return array|false Local stream metadata, or false when closed. */
    public function stream_stat() {
      return $this->stream ? fstat($this->stream) : false;
    }

    /** @return bool Whether the local stream reached EOF. */
    public function stream_eof() {
      return $this->stream ? feof($this->stream) : true;
    }

    /** @return int|false Current byte offset in the local stream. */
    public function stream_tell() {
      return $this->stream ? ftell($this->stream) : false;
    }

    /** @return bool True when the local seek succeeded. */
    public function stream_seek($offset, $whence = SEEK_SET) {
      return $this->stream ? fseek($this->stream, $offset, $whence) === 0 : false;
    }

    /** @return bool True when the local stream was truncated and marked dirty. */
    public function stream_truncate($size) {
      if (!$this->stream) return false;
      $result = ftruncate($this->stream, $size);
      if ($result) $this->dirty = true;
      return $result;
    }

    /** @return bool Result of applying a local file lock. */
    public function stream_lock($operation) {
      return $this->stream ? flock($this->stream, $operation) : false;
    }

    /** @return bool True after flushing local bytes and any pending upload. */
    public function stream_flush() {
      if ($this->stream) fflush($this->stream);
      return $this->dirty ? $this->flush_upload() : true;
    }

    /**
     * Uploads the current temporary file under its exact object name.
     *
     * Fine-grained buckets receive the same predefined and public-read ACLs as
     * media uploads. Uniform-access buckets skip object ACL operations.
     *
     * @param string $mime_type Object content type.
     * @return array Raw Google object metadata.
     * @throws Google_REST_Exception When upload or ACL creation fails.
     */
    private function upload_temporary($mime_type) {
      $client = $this->client->client;
      $manages_acl = !defined('WP_STATELESS_SKIP_ACL_SET') || !WP_STATELESS_SKIP_ACL_SET;
      if ($manages_acl) {
        try {
          $manages_acl = !$client->has_uniform_access();
        } catch (\Throwable $e) {
          $manages_acl = true;
        }
      }
      $media = $client->upload(
        $this->file,
        $this->temporary,
        array('name' => $this->file, 'metadata' => array()),
        $mime_type,
        $manages_acl ? 'bucketOwnerFullControl' : ''
      );
      if ($manages_acl) $this->client->mediaInsertACL($this->file, $media);
      return $media;
    }

    /**
     * Uploads a dirty stream and reports failure without leaking credentials.
     *
     * @return bool True when no upload is needed or the upload succeeds.
     */
    private function flush_upload() {
      if (!$this->dirty || !$this->temporary) return true;
      $type = wp_check_filetype($this->file);
      try {
        $this->upload_temporary(!empty($type['type']) ? $type['type'] : 'application/octet-stream');
      } catch (\Throwable $e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        return false;
      }
      $this->dirty = false;
      return true;
    }

    /** @return bool True after closing the local stream and flushing pending content. */
    public function stream_close() {
      $result = true;
      if ($this->stream) {
        fflush($this->stream);
        fclose($this->stream);
        $this->stream = null;
        if ($this->dirty) $result = $this->flush_upload();
      }
      if ($this->temporary && is_file($this->temporary)) @unlink($this->temporary);
      $this->temporary = '';
      return $result;
    }

    /** @return bool False; stream options are not supported by this wrapper. */
    public function stream_set_option() {
      return false;
    }

    /**
     * Exposes the local resource for operations such as stream_select().
     *
     * @param int $castAs Requested cast type.
     * @return resource|false Native local stream resource, or false when closed.
     */
    public function stream_cast($castAs) {
      return $this->stream ? $this->stream : false;
    }

    /**
     * Creates an empty object when `touch()` is called for a missing path.
     *
     * @param string $path Complete stream URL.
     * @param int $option Stream metadata option; only STREAM_META_TOUCH is supported.
     * @param mixed $value Option value, unused for touch.
     * @return bool True when the object exists or was created.
     */
    public function stream_metadata($path, $option, $value) {
      if ($option !== STREAM_META_TOUCH) return false;
      try {
        $this->open_path($path);
        if ($this->client->media_exists($this->file)) return true;
        $this->temporary_file();
        $this->upload_temporary('application/octet-stream');
        @unlink($this->temporary);
        $this->temporary = '';
        return true;
      } catch (\Throwable $e) {
        if ($this->temporary && is_file($this->temporary)) @unlink($this->temporary);
        $this->temporary = '';
        return false;
      }
    }

    /**
     * Returns file metadata or recognizes a virtual directory from object prefixes.
     *
     * @param string $path Complete stream URL.
     * @param int $flags Stream stat flags, unused.
     * @return array|false Stat array for a file/directory, or false when neither exists.
     */
    public function url_stat($path, $flags) {
      try {
        $this->open_path($path);
        if ($this->file === '' || substr($this->file, -1) === '/') {
          return $this->make_stat(array('mode' => self::DIRECTORY_WRITABLE_MODE));
        }
        $media = $this->client->media_exists($this->file);
        if ($media) {
          $updated = $media->__get('updated');
          return $this->make_stat(array(
            'mode' => self::FILE_WRITABLE_MODE,
            'size' => (int) $media->getSize(),
            'mtime' => $updated ? strtotime($updated) : time(),
          ));
        }
        $objects = $this->client->list_objects(array(
          'prefix' => $this->file . '/',
          'maxResults' => 1,
          'fields' => 'items/name',
        ));
        if (count($objects->getItems())) {
          return $this->make_stat(array('mode' => self::DIRECTORY_WRITABLE_MODE));
        }
        return false;
      } catch (\Throwable $e) {
        return false;
      }
    }

    /**
     * Fills a complete stat array with zeroes for unavailable metadata.
     *
     * @param array $stats Known mode, size, and timestamp values.
     * @return array Complete PHP-compatible stat array.
     */
    private function make_stat($stats) {
      return array_merge(array_fill_keys(array(
        'dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size',
        'atime', 'mtime', 'ctime', 'blksize', 'blocks',
      ), 0), $stats);
    }

    /**
     * Deletes one exact object name.
     *
     * @param string $path Complete stream URL.
     * @return bool True on deletion, false on failure.
     */
    public function unlink($path) {
      try {
        $this->open_path($path);
        $this->client->client->delete_object($this->file);
        return true;
      } catch (\Throwable $e) {
        return false;
      }
    }

    /**
     * Moves a file or every object under a directory prefix.
     *
     * Copies preserve object metadata. Only exact source names and their
     * descendants are moved; similarly prefixed siblings are left untouched.
     *
     * @param string $from Source file or directory URL.
     * @param string $to Destination file or directory URL.
     * @return bool True when at least one object was moved.
     */
    public function rename($from, $to) {
      try {
        $this->open_path($from);
        $source = $this->file;
        $this->open_path($to);
        $destination = $this->file;
        $objects = $this->client->list_all_objects(array('prefix' => $source));
        $renamed = false;
        foreach ($objects as $object) {
          $old_name = $object->getName();
          if ($old_name !== $source && strpos($old_name, rtrim($source, '/') . '/') !== 0) continue;
          $new_name = $destination . substr($old_name, strlen($source));
          if (!$this->client->copy_media($old_name, $new_name)) return false;
          $this->client->client->delete_object($old_name);
          $renamed = true;
        }
        return $renamed;
      } catch (\Throwable $e) {
        return false;
      }
    }

    /**
     * Creates a zero-byte directory marker without invoking media path filters.
     *
     * @param string $path Complete directory URL.
     * @param int $mode Requested directory mode, retained for callback compatibility.
     * @param int $options Stream directory options.
     * @return bool True when the bucket is reachable or the marker was created.
     */
    public function mkdir($path, $mode, $options) {
      try {
        $this->open_path($path);
        if ($this->file === '') return $this->client->is_connected() === true;
        $this->file = rtrim($this->file, '/') . '/';
        $this->temporary_file();
        $this->upload_temporary('application/x-directory');
        @unlink($this->temporary);
        $this->temporary = '';
        return true;
      } catch (\Throwable $e) {
        if ($this->temporary && is_file($this->temporary)) @unlink($this->temporary);
        $this->temporary = '';
        return false;
      }
    }

    /**
     * Deletes a directory marker without deleting descendant objects.
     *
     * @param string $path Complete directory URL.
     * @param int $options Stream directory options, unused.
     * @return bool True when the marker was deleted.
     */
    public function rmdir($path, $options) {
      try {
        $this->open_path($path);
        $this->client->client->delete_object(rtrim($this->file, '/') . '/');
        return true;
      } catch (\Throwable $e) {
        return false;
      }
    }

    /**
     * Lists immediate files and virtual child directories under a prefix.
     *
     * @param string $path Complete directory URL.
     * @param int $options Stream directory options, unused.
     * @return bool True when the prefix was listed successfully.
     */
    public function dir_opendir($path, $options) {
      try {
        $this->open_path($path);
        $prefix = $this->file === '' ? '' : rtrim($this->file, '/') . '/';
        $objects = $this->client->list_all_objects(array('prefix' => $prefix, 'fields' => 'items/name,nextPageToken'));
        $this->directory = array();
        $seen = array();
        foreach ($objects as $object) {
          $remainder = substr($object->getName(), strlen($prefix));
          if ($remainder === '') continue;
          $parts = explode('/', $remainder);
          if ($parts[0] === '' || isset($seen[$parts[0]])) continue;
          $seen[$parts[0]] = true;
          $this->directory[] = $parts[0];
        }
        $this->directory_position = 0;
        return true;
      } catch (\Throwable $e) {
        return false;
      }
    }

    /** @return string|false Next directory entry, or false at the end. */
    public function dir_readdir() {
      if (!array_key_exists($this->directory_position, $this->directory)) return false;
      return $this->directory[$this->directory_position++];
    }

    /** @return bool Always true after resetting the directory position. */
    public function dir_rewinddir() {
      $this->directory_position = 0;
      return true;
    }

    /** @return bool Always true after clearing the directory listing. */
    public function dir_closedir() {
      $this->directory = array();
      $this->directory_position = 0;
      return true;
    }
  }
}
