<?php
/* copied from lib\Google\vendor\google\cloud-storage\src\StreamWrapper.php */

/**
 * A streamWrapper implementation for handling `gs://bucket/path/to/file.jpg`.
 * Note that you can only open a file with mode 'r', 'rb', 'rb', 'w', 'wb', or 'wt'.
 *
 * See: http://php.net/manual/en/class.streamwrapper.php
 */

namespace wpCloud\StatelessMedia {

  use Google\Cloud\Storage\StorageClient;

  if (!class_exists('wpCloud\StatelessMedia\StreamWrapper')) {
    class StreamWrapper extends \Google\Cloud\Storage\StreamWrapper {
      /**
       * @var string Protocol used to open this stream
       */
      private $protocol;

      /**
       * @var Bucket Reference to the bucket the opened file
       *      lives in or will live in.
       */
      private $bucket;

      /**
       * @var string Name of the file opened by this stream.
       */
      private $file;

      /**
       * Callback handler for retrieving information about a file
       *
       * @param string $path The URI to the file
       * @param int $flags Bitwise mask of options
       * @return array|bool
       */
      public function url_stat($path, $flags) {
        $this->_openPath($path);
        // if root dir
        if (empty($this->file)) {
          $stats = [];
          // equivalent to 40777 and 40444 in octal
          if ($is_writable = $this->bucket->isWritable()) {
            $stats['mode'] = $is_writable
              ? self::DIRECTORY_WRITABLE_MODE
              : self::DIRECTORY_READABLE_MODE;
            return $this->makeStatArray($stats);
          }
        }
        return parent::url_stat($path, $flags);
      }

      /**
       * Parse the URL and set protocol, filename and bucket.
       *
       * @param  string $path URL to open
       * @return StorageClient
       */
      private function _openPath($path) {
        $url = (array) parse_url($path) + [
          'scheme' => '',
          'path' => '',
          'host' => ''
        ];
        $this->protocol = $url['scheme'];
        $this->file = ltrim($url['path'], '/');
        $client = self::getClient($this->protocol);
        $this->bucket = $client->bucket($url['host']);
        return $client;
      }

      /**
       * Register a StreamWrapper for reading and writing to Google Storage
       *
       * @param StorageClient $client The StorageClient configuration to use.
       * @param string $protocol The name of the protocol to use. **Defaults to**
       *        `gs`.
       * @throws \RuntimeException
       */
      public static function register(StorageClient $client, $protocol = null) {
        $protocol = $protocol ?: self::DEFAULT_PROTOCOL;
        // we are calling parents register function because only it can set parents pirvate ::$clients property.
        // we are only calling it to set $clients property.
        parent::register($client, $protocol);
        // unregistering the wrapper so that we can register wrapper with our class.
        stream_wrapper_unregister($protocol);
        // registering wrapper with our wpCloud\StatelessMedia\StreamWrapper 
        return stream_wrapper_register($protocol, StreamWrapper::class, STREAM_IS_URL);
      }

      /**
       * Returns the associative array that a `stat()` response expects using the
       * provided stats. Defaults the remaining fields to 0.
       *
       * @param  array $stats Sparse stats entries to set.
       * @return array
       */
      private function makeStatArray($stats) {
        return array_merge(
          array_fill_keys([
            'dev',
            'ino',
            'mode',
            'nlink',
            'uid',
            'gid',
            'rdev',
            'size',
            'atime',
            'mtime',
            'ctime',
            'blksize',
            'blocks'
          ], 0),
          $stats
        );
      }

      /**
       * @param $path
       * @param $option
       * @param $value
       * @return bool
       */
      public function stream_metadata($path, $option, $value) {
        return false;
      }
    }
  }
}
