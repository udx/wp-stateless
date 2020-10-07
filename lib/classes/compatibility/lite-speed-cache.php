<?php
/**
 * Plugin Name: LiteSpeed Cache
 * Plugin URI: https://wordpress.org/plugins/litespeed-cache/
 *
 * Compatibility Description: Ensures compatibility with LiteSpeed Cache plugins "Image WebP Replacement" functions.
 *
 *
 * Reference: https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:cache:lscwp:image-optimization#image_optimization_in_litespeed_cache_for_wordpress
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\LSCacheWP' ) ) {

    class LSCacheWP extends ICompatibility {

      protected $id = 'lscache_wp';
      protected $title = 'LiteSpeed Cache';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_LITESPEED_CACHE';
      protected $description = 'Ensures compatibility with LiteSpeed Cache plugins.';
      protected $plugin_file = 'litespeed-cache/litespeed-cache.php';
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        // Sync image.
        add_action( 'litespeed_img_pull_ori', array( $this, 'sync_image' ), 10, 2 );
        add_action( 'litespeed_img_pull_webp', array( $this, 'sync_webp' ), 10, 2 );
        // Deleting images from GCS.
        add_action( 'litespeed_media_del', array( $this, 'litespeed_media_del' ), 10, 2 );
        // moving images in GCS.
        add_action( 'litespeed_media_rename', array( $this, 'litespeed_media_rename' ), 10, 3 );

        // Adding file md5 for original image to cloud meta.
        add_filter( 'wp_stateless_generate_cloud_meta', array( $this, 'cloud_meta_add_file_md5' ), 10, 5 );


        // override is_internal_file check.
        add_filter( 'litespeed_media_check_ori', array( $this, 'litespeed_media_check_img' ), 10, 2 );
        add_filter( 'litespeed_media_check_webp', array( $this, 'litespeed_media_check_img' ), 10, 2 );

        // litespeed_media_info
        add_filter( 'litespeed_media_info', array( $this, 'litespeed_media_info' ), 10, 3 );

        // Manual sync
        add_action( 'sm:synced::image', array( $this, 'manual_sync_backup_file' ), 10, 2 );
        add_action( 'sm:pre::synced::image', array( $this, 'update_md5_and_manual_sync' ), 10, 1 );

      }

      /**
       * Sync the image when Lite Speed plugin pull the optimized image.
       * We need to overwrite the existing image.
       * @param stdClass Object $row_img
       *       stdClass Object
       *           (
       *               [id] => 28
       *               [post_id] => 494
       *               [optm_status] => notified
       *               [src] => 2019/10/22645b39-asdf.jpg
       *               [srcpath_md5] => ad206986974729e1c8edc9321ed9ba9b
       *               [src_md5] => 9d396b4f7a261a5fac1234b292a7d585
       *               [root_id] => 0
       *               [src_filesize] => 1
       *               [target_filesize] => 0
       *               [target_saved] => 827956
       *               [webp_filesize] => 0
       *               [webp_saved] => 830743
       *               [server_info] => {
       *                      "server":"https:\/\/us1.wp.api.litespeedtech.com",
       *                      "id":"SEU98",
       *                      "ori_md5":"3a7bb6b684d34552d75291ed4c32d399",
       *                      "ori":"https:\/\/us1.wp.api.litespeedtech.com\/dl\/20191011\/c91821\/47721644.jpg",
       *                      "webp_md5":"61d80e1d2799af383c820492a1208846",
       *                      "webp":"https:\/\/us1.wp.api.litespeedtech.com\/dl\/20191011\/c91821\/47721644.jpg.webp"
       *                  }
       *          )
       *
       * @param String $local_file
       *       /var/www/wp-content/uploads/2019/10/22645b39-asdf.jpg
       *
       */
      public function sync_image( $row_img, $local_file ) {
        $rm_ori_bkup = apply_filters('litespeed_conf', 'img_optm-rm_bkup' );
        $gs_name = apply_filters( 'wp_stateless_file_name', $row_img->src );
        $cloud_meta = get_post_meta( $row_img->post_id, 'sm_cloud', true );

        if(empty($cloud_meta)) $cloud_meta = array();

        if( !$rm_ori_bkup ) {
          $extension = pathinfo( $gs_name, PATHINFO_EXTENSION );
          $bk_file = substr( $gs_name, 0, -strlen( $extension ) ) . 'bk.' . $extension;
          $cloud_meta[ 'fileMd5' ][ $bk_file ] = $cloud_meta[ 'fileMd5' ][ $gs_name ];
          do_action( 'sm:sync::copyFile', $gs_name, $bk_file );
        }

        $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $local_file );
        update_post_meta( $row_img->post_id, 'sm_cloud', $cloud_meta );
        do_action( 'sm:sync::syncFile', $gs_name, $local_file, 2 );
      }

      /**
       * Upload webp image after LS pulled the images.
       * @todo put md5_file hash creating here. $row_img might have attachment_id
       * @param $row_img
       * @param $local_file
       */
      public function sync_webp( $row_img, $local_file ) {
        $optm_webp = apply_filters('litespeed_conf', 'img_optm-webp' );
        if( $optm_webp ) {
          $gs_name = apply_filters( 'wp_stateless_file_name', $row_img->src . '.webp' );

          $cloud_meta = get_post_meta( $row_img->post_id, 'sm_cloud', true );
          $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $local_file );
          update_post_meta( $row_img->post_id, 'sm_cloud', $cloud_meta );

          add_filter( 'upload_mimes', array( $this, 'add_webp_mime' ), 10, 2 );
          do_action( 'sm:sync::syncFile', $gs_name, $local_file, 2, array( 'use_root' => true ) );
        }
      }

      /**
       * Bypassing the is_internal_file check on LiteSpeed Cache.
       * That check fails because we are replacing URL with GCS URL.
       * So we need to override it with filter.
       *
       * @todo maybe we can add some validation.
       *
       * @param $return
       * @param $url
       * @return bool
       */
      public function litespeed_media_check_img( $return, $url ) {
        $image_host = ud_get_stateless_media()->get_gs_host();
        if( strpos( $url, $image_host ) === 0 ) {
          return true;
        }
        return $return;
      }

      /**
       * Return information about a file from relative path.
       *
       * @param $info
       * @param $short_file_path
       * @param $post_id
       * @return array( 'url', 'md5', 'size' )
       */
      public function litespeed_media_info( $info, $short_file_path, $post_id ) {
        if( !$post_id ) return $info;

        try {
          $metadata = wp_get_attachment_metadata( $post_id );
          $cloud_meta = get_post_meta( $post_id, 'sm_cloud', true );

          if( !empty( $metadata[ 'gs_link' ] ) ) {
            $short_file_path = apply_filters( 'wp_stateless_file_name', $short_file_path );
            $url = ud_get_stateless_media()->get_gs_host() . '/' . $short_file_path;
            $md5 = !empty( $cloud_meta[ 'fileMd5' ][ $short_file_path ] ) ? $cloud_meta[ 'fileMd5' ][ $short_file_path ] : null;

            if( $metadata[ 'file' ] == $short_file_path ) {
              $url = $metadata[ 'gs_link' ];
            } else {
              foreach( $metadata[ 'sizes' ] as $size => $meta ) {
                if( $meta[ 'file' ] == basename( $short_file_path ) ) {
                  $url = $meta[ 'gs_link' ];
                  break;
                }
              }
            }

            if( $md5 ) {
              $info = array( 'url' => $url, 'md5' => $md5, 'size' => 1, );
            }
          }
        } catch( \Throwable $th ) {
          error_log( print_r( $th, true ) );
        }

        return $info;
      }


      /**
       * Deletes a file in GCS and remove the hash from cloud meta.

       * @param $short_file_path
       * @param $post_id
       */
      public function litespeed_media_del( $short_file_path, $post_id ) {
        $short_file_path = apply_filters( 'wp_stateless_file_name', $short_file_path );
        do_action( 'sm:sync::deleteFile', $short_file_path );
      }

      /**
       * Hooks into the rename function of the LS cache.
       * And move the file in GCS.
       * Also update the md5_file hash on cloud meta.
       *
       * @param $short_file_path
       * @param $short_file_path_new
       * @param $post_id
       */
      public function litespeed_media_rename( $short_file_path, $short_file_path_new, $post_id ) {
        $short_file_path = apply_filters( 'wp_stateless_file_name', $short_file_path );
        $short_file_path_new = apply_filters( 'wp_stateless_file_name', $short_file_path_new );

        // copy file to the new location and delete the old one.
        do_action( 'sm:sync::moveFile', $short_file_path, $short_file_path_new );

        $this->update_hash( $post_id, $short_file_path_new, $short_file_path );
      }

      /**
       * add_webp_mime
       * @param $t
       * @param $user
       * @return mixed
       */
      public function add_webp_mime( $t, $user ) {
        $t[ 'webp' ] = 'image/webp';
        return $t;
      }

      /**
       * Move file hash from one key to another.
       *
       * @param $attachment_id
       * @param $gs_name_new key to store md5_file.
       * @param $gs_name_old whether to get md5 from another entry.
       * @param bool $delete whether only remove the key.
       * @return bool
       */
      public function update_hash( $attachment_id, $gs_name_new, $gs_name_old, $delete = false ) {
        try {
          $cloud_meta = get_post_meta( $attachment_id, 'sm_cloud', true );

          if( !$delete ) {
            if( $gs_name_old && !empty( $cloud_meta[ 'fileMd5' ][ $gs_name_old ] ) ) {
              $cloud_meta[ 'fileMd5' ][ $gs_name_new ] = $cloud_meta[ 'fileMd5' ][ $gs_name_old ];
            } else {
              $url = ud_get_stateless_media()->get_gs_host() . '/' . $gs_name_new;
              $cloud_meta[ 'fileMd5' ][ $gs_name_new ] = md5_file( $url );
            }
          }

          if( isset( $cloud_meta[ 'fileMd5' ][ $gs_name_old ] ) ) unset( $cloud_meta[ 'fileMd5' ][ $gs_name_old ] );
          update_post_meta( $attachment_id, 'sm_cloud', $cloud_meta );
          return true;
        } catch( \Throwable $th ) {
          error_log( print_r( $th, true ) );
          return false;
        }
        return false;
      }

      /**
       * Adds file hash to cloud meta, so that we can use it later.
       *
       * @param $cloud_meta
       * @param $media
       * @param $image_size
       * @param $img
       * @param $bucketLink
       * @return array $cloud_meta with fileMd5
       */
      public function cloud_meta_add_file_md5( $cloud_meta, $media, $image_size, $img, $bucketLink ) {
        if( $file_hash = md5_file( $img[ 'path' ] ) ) {
          $gs_name = !empty( $media[ 'name' ] ) ? $media[ 'name' ] : $img[ 'gs_name' ];
          $extension = pathinfo( $gs_name, PATHINFO_EXTENSION );
          $bk_file = substr( $gs_name, 0, -strlen( $extension ) ) . 'bk.' . $extension;

          // Storing file hash
          $cloud_meta[ 'fileMd5' ][ $gs_name ] = $file_hash;
        }

        return $cloud_meta;
      }

      /**
       * On manual sync/regenerate we need to sync backup/webp files.
       *
       * @param $attachment_id
       * @param $metadata
       */
      public function manual_sync_backup_file( $attachment_id, $metadata ) {
        $cloud_meta = get_post_meta( $attachment_id, 'sm_cloud', true );

        if( !empty( $cloud_meta[ 'fileMd5' ] ) ) {
          $upload_dir = wp_upload_dir();
          $fileMd5 = $cloud_meta[ 'fileMd5' ];
          $root_dir = ud_get_stateless_media()->get( 'sm.root_dir' );
          $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);
          $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.

          foreach( $fileMd5 as $gs_name => $value ) {
            $_gs_name = str_replace( $root_dir, '', $gs_name );
            $local_file = $upload_dir[ 'basedir' ] . '/' . trim( $_gs_name, '/' );

            do_action( 'sm:sync::syncFile', $gs_name, $local_file, true );
          }
        }
      }

      /**
       * On manual sync/regenerate wp regenerate all image sizes.
       * So to keep the file md5 accurate we need to update it after image is regenerated.
       *
       * Manual sync if it's the first time syncing and image optimized before enabling Stateless
       *
       * @param $attachment_id
       */
      public function update_md5_and_manual_sync( $attachment_id ) {
        $cloud_meta = get_post_meta( $attachment_id, 'sm_cloud', true );
        $metadata = wp_get_attachment_metadata( $attachment_id );
        $image_sizes = Utility::get_path_and_url( $metadata, $attachment_id );

        if( !empty( $cloud_meta[ 'fileMd5' ] ) ) {
          foreach( $image_sizes as $img ) {
            $cloud_meta[ 'fileMd5' ][ $img[ 'gs_name' ] ] = md5_file( $img[ 'path' ] );
          }
        }

        if( empty( $cloud_meta[ 'fileMd5' ] ) || count( $cloud_meta[ 'fileMd5' ] ) <= 1 ) {
          if( empty( $cloud_meta ) ) {
            $cloud_meta = array();
          }

          // In case image optimized before enabling Stateless.
          // We only need to copy from local to GCS, otherwise file_md5 meta should be available.
          foreach( $image_sizes as $img ) {
            $file_path = $img[ 'path' ];
            $rm_ori_bkup = apply_filters('litespeed_conf', 'img_optm-rm_bkup' );
            $optm_webp = apply_filters('litespeed_conf', 'img_optm-webp' );

            if( !$rm_ori_bkup ) {
              $extension = pathinfo( $file_path, PATHINFO_EXTENSION );
              $bk_file = substr( $file_path, 0, -strlen( $extension ) ) . 'bk.' . $extension;
              $bk_file_optm = substr( $file_path, 0, -strlen( $extension ) ) . 'bk.optm.' . $extension;
              if( file_exists( $bk_file ) ) {
                $gs_name = apply_filters( 'wp_stateless_file_name', $bk_file );
                $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $bk_file );
                do_action( 'sm:sync::syncFile', $gs_name, $bk_file );
              } elseif( file_exists( $bk_file_optm ) ) {
                $gs_name = apply_filters( 'wp_stateless_file_name', $bk_file_optm );
                $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $bk_file_optm );
                do_action( 'sm:sync::syncFile', $gs_name, $bk_file_optm );
              }
            }

            if( $optm_webp ) {
              $gs_name_webp = $file_path . '.webp';
              $gs_name_webp_optm = $file_path . '.optm.webp';
              if( file_exists( $gs_name_webp ) ) {
                $gs_name = apply_filters( 'wp_stateless_file_name', $gs_name_webp );
                $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $gs_name_webp );
                do_action( 'sm:sync::syncFile', $gs_name, $gs_name_webp );
              } elseif( file_exists( $gs_name_webp_optm ) ) {
                $gs_name = apply_filters( 'wp_stateless_file_name', $gs_name_webp_optm );
                $cloud_meta[ 'fileMd5' ][ $gs_name ] = md5_file( $gs_name_webp_optm );
                do_action( 'sm:sync::syncFile', $gs_name, $gs_name_webp_optm );
              }
            }

          }

        }

        update_post_meta( $attachment_id, 'sm_cloud', $cloud_meta );
      }

    }

  }

}
