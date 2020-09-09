<?php
/**
 * Plugin Name: ShortPixel Image Optimizer
 * Plugin URI: https://wordpress.org/plugins/shortpixel-image-optimiser/
 *
 * Compatibility Description: Ensures compatibility with ShortPixel Image Optimizer.
 *
 * We don't need to download image to server from GCS to optimize image. As it can directly use GCS URL.
 *
 * @todo Use backup version of image on Regenerate and Sync with GCS.
 * @todo Restore image now download from backup dir then upload to regular GCS path again. We need to fix that.
 *          https://cloud.google.com/storage/docs/renaming-copying-moving-objects#copy
 * @todo implement a customized version of ShortPixelMetaFacade::getURLsAndPATHs() function.
 * @todo convert GCS URL from http://storage.googleapis.com/udx-ci-develop-alim/ to http://udx-ci-develop-alim.storage.googleapis.com
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\ShortPixel' ) ) {

    class ShortPixel extends ICompatibility {

      protected $id = 'shortpixel';
      protected $title = 'ShortPixel Image Optimizer';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_SHORTPIXEL';
      protected $description = 'Ensures compatibility with ShortPixel Image Optimizer.';
      protected $plugin_file = 'shortpixel-image-optimiser/wp-shortpixel.php';
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        add_action( 'shortpixel_image_optimised', array( $this, 'shortpixel_image_optimised' ) );
        add_filter( 'shortpixel_image_exists', array( $this, 'shortpixel_image_exists' ), 10, 3 );
        // A way to disable the URL conversion to subdomain format.
        if( !defined( 'WP_STATELESS_MEDIA_SHORTPIXEL_DISABLE_SUBDOMAIN_LINK' ) || !WP_STATELESS_MEDIA_SHORTPIXEL_DISABLE_SUBDOMAIN_LINK ) {
          // URL conversion to subdomain format {bucket}.storage.googleapis.com instead of storage.googleapis.com/{bucket}
          // So that ShortPixel don't count all Stateless request as one domain.
          add_filter( 'shortpixel_image_urls', array( $this, 'shortpixel_image_urls' ), 10, 2 );
        }
        add_filter( 'shortpixel_skip_backup', array( $this, 'shortpixel_skip_backup' ), 10, 3 );
        add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 10, 2 );
        // Remove backup and webp version of image.
        add_filter( 'shortpixel_skip_delete_backups_and_webps', array( $this, 'shortpixel_skip_delete_backups_and_webps' ), 10, 2 );
        add_filter( 'shortpixel_backup_folder', array( $this, 'getBackupFolderAny' ), 10, 3 );
        add_filter( 'wp_stateless_add_media_args', array( $this, 'wp_stateless_add_media_args' ) );
        add_filter( 'shortpixel_webp_image_base', array( $this, 'shortpixel_webp_image_base' ), 10, 2 );
        // add_filter( 'shortpixel_skip_restore_image', array( $this, 'shortpixel_skip_restore_image' ), 10, 2 );

        add_action( 'shortpixel_before_restore_image', array( $this, 'shortpixel_before_restore_image' ) );
        add_action( 'shortpixel_after_restore_image', array( $this, 'handleRestoreBackup' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'shortPixelJS' ) );
        // Sync from sync tab
        add_action( 'sm:synced::image', array( $this, 'sync_backup_file' ), 10, 2 );
        add_action( 'sm:synced::image', array( $this, 'sync_webp_file' ), 10, 2 );
      }

      public function shortPixelJS() {
        $upload_dir = wp_upload_dir();

        wp_enqueue_script( 'stateless-short-pixel', ud_get_stateless_media()->path( 'lib/classes/compatibility/js/shortpixel.js', 'url' ), array( 'shortpixel' ), '', true );

        $image_host = ud_get_stateless_media()->get_gs_host();
        $bucketLink = apply_filters( 'wp_stateless_bucket_link', $image_host );

        wp_localize_script( 'stateless-short-pixel', '_stateless_short_pixel', array( 'baseurl' => $upload_dir[ 'baseurl' ], 'bucketLink' => $bucketLink, ) );

      }

      /**
       * Return Path of ShortPixel backup path.
       * Bypass the checking whether a backup file exist when returning backup path.
       * @todo check on GCS if backup really exist. Maybe we can implement transient caching for performance.
       *
       * @param $ret
       * @param $file
       * @param $thumbs
       * @return string
       */
      public function getBackupFolderAny( $ret, $file, $thumbs ) {
        if( $ret == false ) {
          $fullSubDir = $this->returnSubDir( $file );
          $ret = SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir;

        }
        return $ret;
      }

      /**
       * Check whether image exist on GCS.
       * Only when image is not available on server.
       *
       * @param $return
       * @param $path
       * @param null $id
       * @return bool
       */
      public function shortpixel_image_exists( $return, $path, $id = null ) {
        if( $return ) return $return;

        $key = "stateless_url_to_postid_" . md5( $path );
        $return = get_transient( $key );
        // echo "\npath: $path \nKey: $key\nReturn: $return\nID: $id\n  ";
        if( !$return ) {
          // Checking by matching file name in gs_name and $path.
          if( !empty( $id ) ) {
            $metadata = wp_get_attachment_metadata( $id );
            $basename = basename( $path );
            if( !empty( $metadata[ 'gs_name' ] ) ) {
              $gs_basename = basename( $metadata[ 'gs_name' ] );
              if( $gs_basename == $basename ) {
                $return = true;
              }

              if( is_array( $metadata[ 'sizes' ] ) ) {
                foreach( $metadata[ 'sizes' ] as $key => &$data ) {
                  if( empty( $data[ 'gs_name' ] ) ) continue;
                  $gs_basename = basename( $data[ 'gs_name' ] );
                  if( $gs_basename == $basename ) {
                    $return = true;
                  }
                }
              }
            }
          } // Directly check on GCS if image exist.
          else if( empty( $id ) ) {
            $wp_uploads_dir = wp_get_upload_dir();
            $gs_name = str_replace( trailingslashit( $wp_uploads_dir[ 'basedir' ] ), '', $path );
            $gs_name = str_replace( trailingslashit( $wp_uploads_dir[ 'baseurl' ] ), '', $gs_name );
            $gs_name = str_replace( trailingslashit( ud_get_stateless_media()->get_gs_host() ), '', $gs_name );
            $gs_name = apply_filters( 'wp_stateless_file_name', $gs_name );
            if( $media = ud_get_stateless_media()->get_client()->media_exists( $gs_name ) ) {
              $return = true;
            }
          }
          set_transient( $key, $return, 10 * MINUTE_IN_SECONDS );
        }
        return $return;
      }

      /**
       * Short-circuit filter.
       * We need to remove backup image from GCS when shortpixel tries to delete from server.
       * We are returning true to short-circuit in ephemeral mode, because file not exist in server.
       *
       * @param $return
       * @param $paths
       * @return bool
       */
      public function shortpixel_skip_delete_backups_and_webps( $return, $paths ) {
        if( empty( $paths ) || !is_array( $paths ) ) return $return;

        $sp__uploads = wp_upload_dir();
        $fullSubDir = $this->returnSubDir( $paths[ 0 ] );
        $backup_path = SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir;

        foreach( $paths as $key => $path ) {
          // Removing backup
          $name = apply_filters( 'wp_stateless_file_name', SHORTPIXEL_BACKUP . '/' . $fullSubDir . basename( $path ) );
          do_action( 'sm:sync::deleteFile', $name );

          // Removing WebP
          $backup_images = \WPShortPixelSettings::getOpt( 'wp-short-create-webp' );
          if( $backup_images ) {
            $name = str_replace( $sp__uploads[ 'basedir' ], '', $path );
            $name = apply_filters( 'wp_stateless_file_name', $name . '.webp' );
            do_action( 'sm:sync::deleteFile', $name );
          }
        }

        if( ud_get_stateless_media()->get( 'sm.mode' ) == 'ephemeral' ) {
          return true;
        }
        // When ephemeral mode isn't set backup files will be available in server. So no short-circuit.
        return $return;
      }

      /**
       * Skip the original backup process in ephemeral mode.
       *
       * @param $return
       * @param $mainPath
       * @param $PATHs
       * @return bool
       */
      public function shortpixel_skip_backup( $return, $mainPath, $PATHs ) {
        if( ud_get_stateless_media()->get( 'sm.mode' ) == 'ephemeral' ) {
          return true;
        }
        return $return;
      }

      /**
       * In ephemeral mode we need to take backup directly from original location.
       * Because we will skip the backup process of shortpixel.
       *
       * @param $metadata
       * @param $attachment_id
       * @return mixed
       */
      public function wp_update_attachment_metadata( $metadata, $attachment_id ) {
        if( ud_get_stateless_media()->get( 'sm.mode' ) == 'ephemeral' ) {
          $backup_images = \WPShortPixelSettings::getOpt( 'wp-short-backup_images' );
          if( $backup_images ) {
            $this->sync_backup_file( $attachment_id, $metadata, false, array( 'before_optimization' => true ) );
          }
        }
        return $metadata;
      }

      /**
       * Sync image after optimization.
       *
       * @param $id
       */
      public function shortpixel_image_optimised( $id ) {
        $metadata = wp_get_attachment_metadata( $id );
        ud_get_stateless_media()->add_media( $metadata, $id, true );
        // Sync the webp to GCS
        $create_webp = \WPShortPixelSettings::getOpt( 'wp-short-create-webp' );
        if( $create_webp ) {
          $this->sync_webp_file($id, $metadata);
        }
        // Don't needed in ephemeral mode. In ephemeral mode the back will be sync once on wp_update_attachment_metadata filter.
        if( ud_get_stateless_media()->get( 'sm.mode' ) !== 'ephemeral' ) {
          $this->sync_backup_file( $id, $metadata, true );
        }
      }

      /**
       * Before shortpixel tries to restore from backup we need to make files available on server.
       *
       * @param $id
       * @param null $metadata
       */
      public function shortpixel_before_restore_image( $id, $metadata = null ) {
        $this->sync_backup_file( $id, $metadata, true, array( 'download' => true ) );
      }

      /**
       * Disable default shortpixel restore and directly update image on GCS from backup copy in GCS.
       *
       * @param $return
       * @param null $id
       * @return bool
       */
      public function shortpixel_skip_restore_image( $return, $id = null ) {
        if( ud_get_stateless_media()->get( 'sm.mode' ) === 'ephemeral' ) {
          $this->client = ud_get_stateless_media()->get_client();
          $this->client->copy_media( 'localhost/ShortpixelBackups/wp-content/uploads/2019/04/htpps.png', 'localhost/2019/04/htpps.png' );
          return true;
        }
        return $return;
      }

      /**
       * Sync backup image
       *
       * @param $id
       * @param null $metadata
       * @param bool $force
       * @param array $args
       * before_optimization : pass true if you want to sync directly from original path instead of backup path.
       */
      public function sync_backup_file( $id, $metadata = null, $force = false, $args = array() ) {
        $args = wp_parse_args( $args, array( 'download' => false, // whether to only download.
          'before_optimization' => false, // whether to delete local file in ephemeral mode.
        ) );

        /* Get metadata in case if method is called directly. */
        if( empty( $metadata ) ) {
          $metadata = wp_get_attachment_metadata( $id );
        }
        /* Now we go through all available image sizes and upload them to Google Storage */
        if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {

          // Sync backup file with GCS
          $file_path = get_attached_file( $id );
          $fullSubDir = $this->returnSubDir( $file_path );
          $backup_path = SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir;
          if( $args[ 'before_optimization' ] ) {
            $upload_dir = wp_upload_dir();
            $backup_path = $upload_dir[ 'basedir' ] . '/' . dirname( $metadata[ 'file' ] );
            $args = array( 'ephemeral' => false );
          }

          $absolutePath = trailingslashit( $backup_path ) . basename( $metadata[ 'file' ] );
          $name = apply_filters( 'wp_stateless_file_name', SHORTPIXEL_BACKUP . '/' . $fullSubDir . basename( $metadata[ 'file' ] ) );
          do_action( 'sm:sync::syncFile', $name, $absolutePath, $force, $args );

          foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {
            $absolutePath = trailingslashit( $backup_path ) . $data[ 'file' ];
            $name = apply_filters( 'wp_stateless_file_name', SHORTPIXEL_BACKUP . '/' . $fullSubDir . $data[ 'file' ] );

            do_action( 'sm:sync::syncFile', $name, $absolutePath, $force, $args );
          }

        }
      }

      /**
       * Sync from sync tab
       *
       * @param $id
       * @param null $metadata
       */
      public function sync_webp_file( $id, $metadata = null ) {
        /* Get metadata in case if method is called directly. */
        if( empty( $metadata ) ) {
          $metadata = wp_get_attachment_metadata( $id );
        }
        add_filter( 'upload_mimes', array( $this, 'add_webp_mime' ), 10, 2 );
        // Sync the webp to GCS
        ud_get_stateless_media()->add_media( $metadata, $id, true, array( 'is_webp' => '.webp' ) );
        remove_filter( 'upload_mimes', array( $this, 'add_webp_mime' ), 10 );
      }

      /**
       * return subdir for that particular attached file - if it's media library then last 3 path items, otherwise substract the uploads path
       * Has trailing directory separator (/)
       *
       * @copied from shortpixel-image-optimiser\class\db\shortpixel-meta-facade.php
       * @param type $file
       * @return string
       */
      public function returnSubDir( $file ) {
        $hp = wp_normalize_path( get_home_path() );
        $file = wp_normalize_path( $file );
        $sp__uploads = wp_upload_dir();
        if( strstr( $file, $hp ) ) {
          $path = str_replace( $hp, "", $file );
        } elseif( strstr( $file, dirname( WP_CONTENT_DIR ) ) ) { //in some situations the content dir is not inside the root, check this also (ex. single.shortpixel.com)
          $path = str_replace( trailingslashit( dirname( WP_CONTENT_DIR ) ), "", $file );
        } elseif( ( strstr( realpath( $file ), realpath( $hp ) ) ) ) {
          $path = str_replace( realpath( $hp ), "", realpath( $file ) );
        } elseif( strstr( $file, trailingslashit( dirname( dirname( $sp__uploads[ 'basedir' ] ) ) ) ) ) {
          $path = str_replace( trailingslashit( dirname( dirname( $sp__uploads[ 'basedir' ] ) ) ), "", $file );
        } else {
          $path = ( substr( $file, 1 ) );
        }
        $pathArr = explode( '/', $path );
        unset( $pathArr[ count( $pathArr ) - 1 ] );
        return implode( '/', $pathArr ) . '/';
      }

      /**
       * Sync images after shortpixel restore them from backup.
       *
       * @param $attachmentID
       */
      public function handleRestoreBackup( $attachmentID ) {
        $metadata = wp_get_attachment_metadata( $attachmentID );
        $this->add_media( $metadata, $attachmentID );
      }

      /**
       * Customized version of wpCloud\StatelessMedia\Utility::add_media()
       * to satisfied our need in restore backup
       * If a image isn't restored from backup then ignore it.
       *
       * @param $metadata
       * @param $attachment_id
       */
      public static function add_media( $metadata, $attachment_id ) {
        $upload_dir = wp_upload_dir();

        $client = ud_get_stateless_media()->get_client();

        if( !is_wp_error( $client ) ) {

          $fullsizepath = wp_normalize_path( get_attached_file( $attachment_id ) );
          // Make non-images uploadable.
          if( empty( $metadata[ 'file' ] ) && $attachment_id ) {
            $metadata = array( "file" => str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', get_attached_file( $attachment_id ) ) );
          }

          $file = wp_normalize_path( $metadata[ 'file' ] );
          $image_host = ud_get_stateless_media()->get_gs_host();
          $bucketLink = apply_filters( 'wp_stateless_bucket_link', $image_host );
          $_cacheControl = \wpCloud\StatelessMedia\Utility::getCacheControl( $attachment_id, $metadata, null );
          $_contentDisposition = \wpCloud\StatelessMedia\Utility::getContentDisposition( $attachment_id, $metadata, null );
          $_metadata = array( "width" => isset( $metadata[ 'width' ] ) ? $metadata[ 'width' ] : null, "height" => isset( $metadata[ 'height' ] ) ? $metadata[ 'height' ] : null, 'object-id' => $attachment_id, 'source-id' => md5( $attachment_id . ud_get_stateless_media()->get( 'sm.bucket' ) ), 'file-hash' => md5( $metadata[ 'file' ] ) );

          if( file_exists( $fullsizepath ) ) {
            $file = apply_filters( 'wp_stateless_file_name', $file );

            /* Add default image */
            $media = $client->add_media( $_mediaOptions = array_filter( array( 'force' => true, 'name' => $file, 'absolutePath' => wp_normalize_path( get_attached_file( $attachment_id ) ), 'cacheControl' => $_cacheControl, 'contentDisposition' => $_contentDisposition, 'mimeType' => get_post_mime_type( $attachment_id ), 'metadata' => $_metadata ) ) );

            // ephemeral mode: we don't need the local version.
            if( ud_get_stateless_media()->get( 'sm.mode' ) === 'ephemeral' ) {
              unlink( $fullsizepath );
            }
          }

          /* Now we go through all available image sizes and upload them to Google Storage */
          if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {

            $path = wp_normalize_path( dirname( get_attached_file( $attachment_id ) ) );
            $mediaPath = apply_filters( 'wp_stateless_file_name', trim( dirname( $metadata[ 'file' ] ), '\/\\' ) );

            foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {

              $absolutePath = wp_normalize_path( $path . '/' . $data[ 'file' ] );

              if( !file_exists( $absolutePath ) ) {
                continue;
              }

              /* Add 'image size' image */
              $media = $client->add_media( array( 'force' => true, 'name' => $file_path = trim( $mediaPath . '/' . $data[ 'file' ], '/' ), 'absolutePath' => $absolutePath, 'cacheControl' => $_cacheControl, 'contentDisposition' => $_contentDisposition, 'mimeType' => $data[ 'mime-type' ], 'metadata' => array_merge( $_metadata, array( 'width' => $data[ 'width' ], 'height' => $data[ 'height' ], 'child-of' => $attachment_id, 'file-hash' => md5( $data[ 'file' ] ) ) ) ) );

              /* Break if we have errors. */
              if( !is_wp_error( $media ) ) {
                // ephemeral mode: we don't need the local version.
                if( ud_get_stateless_media()->get( 'sm.mode' ) === 'ephemeral' ) {
                  unlink( $absolutePath );
                }
              }

            }

          }

        }
      }
      // End add_media

      /**
       * modifying gs_name and absolutePath so that we can upload webp image using the same Utility::add_media function.
       *
       * @param $args
       * @return mixed
       */
      public function wp_stateless_add_media_args( $args ) {
        if( !empty( $args[ 'is_webp' ] ) && $args[ 'is_webp' ] ) {
          if( \file_exists( $args[ 'absolutePath' ] . '.webp' ) ) {
            $args[ 'name' ] = $args[ 'name' ] . '.webp';
            $args[ 'absolutePath' ] = $args[ 'absolutePath' ] . '.webp';
          } else {
            $pathinfo = pathinfo( $args[ 'absolutePath' ] );
            $absolutePath = trailingslashit( $pathinfo[ 'dirname' ] ) . $pathinfo[ 'filename' ] . '.webp';
            if( file_exists( $absolutePath ) ) {
              $args[ 'name' ] = $args[ 'name' ] . '.webp';
              $args[ 'absolutePath' ] = $absolutePath;
            }
          }
          $args[ 'mimeType' ] = 'image/webp';
        }
        return $args;
      }

      /**
       * Bypass server url check and return base url for GCS image.
       *
       * @param $imageBase
       * @param $src
       * @return mixed
       */
      public function shortpixel_webp_image_base( $imageBase, $src ) {
        $gs_link = \ud_get_stateless_media()->convert_to_gs_link( $src, true );
        if( $gs_link ) {
          $imageBase = trailingslashit( dirname( $gs_link ) );
        }
        return $imageBase;
      }

      /**
       * @param $URLs
       * @param $id
       * @return mixed
       */
      public function shortpixel_image_urls( $URLs, $id ) {
        foreach( $URLs as $key => $url ) {
          $url_parts = wp_parse_url( $url );
          if( $url_parts[ 'host' ] == 'storage.googleapis.com' ) {
            if( preg_match( "@(^/?.*?/)(.*)@", $url_parts[ 'path' ], $matches ) ) {
              $bucket = trim( $matches[ 1 ], '/' );
              $url_parts[ 'path' ] = $matches[ 2 ];
              $url_parts[ 'host' ] = $bucket . '.' . $url_parts[ 'host' ];
              $URLs[ $key ] = Utility::join_url( $url_parts );
            }
          }
        }
        return $URLs;
      }

    }

  }

}
