<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\StreamWrapper')) {
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

    public function __destruct() {
      $this->stream_close();
    }

    public static function register($client, $protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      if (is_wp_error($client)) return false;
      if (in_array($protocol, stream_get_wrappers(), true)) {
        stream_wrapper_unregister($protocol);
      }
      self::$clients[$protocol] = $client;
      return stream_wrapper_register($protocol, StreamWrapper::class, STREAM_IS_URL);
    }

    public static function unregister($protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      if (in_array($protocol, stream_get_wrappers(), true)) stream_wrapper_unregister($protocol);
      unset(self::$clients[$protocol]);
    }

    public static function getClient($protocol = null) {
      $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
      return isset(self::$clients[$protocol]) ? self::$clients[$protocol] : null;
    }

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

    private function temporary_file() {
      $path = wp_tempnam('wp-stateless-stream');
      if (!$path) throw new \RuntimeException('Unable to allocate a temporary stream file.');
      $this->temporary = $path;
      return $path;
    }

    private function return_error($message, $flags) {
      if ($flags & STREAM_REPORT_ERRORS) trigger_error($message, E_USER_WARNING);
      return false;
    }

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

    public function stream_read($count) {
      return $this->stream ? fread($this->stream, $count) : false;
    }

    public function stream_write($data) {
      if (!$this->stream) return false;
      $written = fwrite($this->stream, $data);
      if ($written) $this->dirty = true;
      return $written;
    }

    public function stream_stat() {
      return $this->stream ? fstat($this->stream) : false;
    }

    public function stream_eof() {
      return $this->stream ? feof($this->stream) : true;
    }

    public function stream_tell() {
      return $this->stream ? ftell($this->stream) : false;
    }

    public function stream_seek($offset, $whence = SEEK_SET) {
      return $this->stream ? fseek($this->stream, $offset, $whence) === 0 : false;
    }

    public function stream_truncate($size) {
      if (!$this->stream) return false;
      $result = ftruncate($this->stream, $size);
      if ($result) $this->dirty = true;
      return $result;
    }

    public function stream_lock($operation) {
      return $this->stream ? flock($this->stream, $operation) : false;
    }

    public function stream_flush() {
      if ($this->stream) fflush($this->stream);
      return $this->dirty ? $this->flush_upload() : true;
    }

    private function flush_upload() {
      if (!$this->dirty || !$this->temporary) return true;
      $type = wp_check_filetype($this->file);
      $result = $this->client->add_media(array(
        'force' => true,
        'use_root' => false,
        'name' => $this->file,
        'absolutePath' => $this->temporary,
        'mimeType' => !empty($type['type']) ? $type['type'] : 'application/octet-stream',
        'metadata' => array(),
      ));
      if (is_wp_error($result)) {
        trigger_error($result->get_error_message(), E_USER_WARNING);
        return false;
      }
      $this->dirty = false;
      return true;
    }

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

    public function stream_set_option() {
      return false;
    }

    public function stream_metadata($path, $option, $value) {
      if ($option !== STREAM_META_TOUCH) return false;
      try {
        $this->open_path($path);
        if ($this->client->media_exists($this->file)) return true;
        $temporary = $this->temporary_file();
        $result = $this->client->add_media(array(
          'force' => true,
          'use_root' => false,
          'name' => $this->file,
          'absolutePath' => $temporary,
          'mimeType' => 'application/octet-stream',
        ));
        @unlink($temporary);
        $this->temporary = '';
        return !is_wp_error($result);
      } catch (\Throwable $e) {
        return false;
      }
    }

    public function url_stat($path, $flags) {
      try {
        $this->open_path($path);
        if ($this->file === '' || substr($this->file, -1) === '/') {
          return $this->make_stat(array('mode' => self::DIRECTORY_WRITABLE_MODE));
        }
        $media = $this->client->media_exists($this->file);
        if (!$media) return false;
        $updated = $media->__get('updated');
        return $this->make_stat(array(
          'mode' => self::FILE_WRITABLE_MODE,
          'size' => (int) $media->getSize(),
          'mtime' => $updated ? strtotime($updated) : time(),
        ));
      } catch (\Throwable $e) {
        return false;
      }
    }

    private function make_stat($stats) {
      return array_merge(array_fill_keys(array(
        'dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size',
        'atime', 'mtime', 'ctime', 'blksize', 'blocks',
      ), 0), $stats);
    }

    public function unlink($path) {
      try {
        $this->open_path($path);
        $result = $this->client->remove_media($this->file, '', false);
        return !is_wp_error($result);
      } catch (\Throwable $e) {
        return false;
      }
    }

    public function rename($from, $to) {
      try {
        $this->open_path($from);
        $source = $this->file;
        $this->open_path($to);
        $destination = $this->file;
        $media = $this->client->copy_media($source, $destination);
        if (!$media) return false;
        $removed = $this->client->remove_media($source, '', false);
        return !is_wp_error($removed);
      } catch (\Throwable $e) {
        return false;
      }
    }

    public function mkdir($path, $mode, $options) {
      try {
        $this->open_path($path);
        if ($this->file === '') return $this->client->is_connected() === true;
        $this->file = rtrim($this->file, '/') . '/';
        $temporary = $this->temporary_file();
        $result = $this->client->add_media(array(
          'force' => true,
          'use_root' => false,
          'name' => $this->file,
          'absolutePath' => $temporary,
          'mimeType' => 'application/x-directory',
        ));
        @unlink($temporary);
        $this->temporary = '';
        return !is_wp_error($result);
      } catch (\Throwable $e) {
        return false;
      }
    }

    public function rmdir($path, $options) {
      try {
        $this->open_path($path);
        $result = $this->client->remove_media(rtrim($this->file, '/') . '/', '', false);
        return !is_wp_error($result);
      } catch (\Throwable $e) {
        return false;
      }
    }

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

    public function dir_readdir() {
      if (!array_key_exists($this->directory_position, $this->directory)) return false;
      return $this->directory[$this->directory_position++];
    }

    public function dir_rewinddir() {
      $this->directory_position = 0;
      return true;
    }

    public function dir_closedir() {
      $this->directory = array();
      $this->directory_position = 0;
      return true;
    }
  }
}
