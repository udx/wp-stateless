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
 * @todo configure as image optimization plugin.
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\LSCacheWP')) {
        
        class LSCacheWP extends ICompatibility {

            protected $id = 'lscache_wp';
            protected $title = 'LiteSpeed Cache';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_LITESPEED_CACHE';
            protected $description = 'Ensures compatibility with LiteSpeed Cache plugins "Image WebP Replacement" functions.';
            protected $plugin_file = 'litespeed-cache/litespeed-cache.php';

            public function module_init($sm){
                // Sync image.
                add_action( 'litespeed_img_pull_ori', array($this, 'sync_image'), 10, 2 );
                add_action( 'litespeed_img_pull_webp', array($this, 'sync_image'), 10, 2 );
                add_action( 'wp_stateless_media_synced', array($this, 'sync_attachment'), 10, 4 );

                // override is_internal_file check.
                add_filter( 'litespeed_media_check_ori', array($this, 'litespeed_media_check_img'), 10, 2 );
                add_filter( 'litespeed_media_check_webp', array($this, 'litespeed_media_check_img'), 10, 2 );
            }

            /**
             * Sync the image when Lite Speed plugin pull the optimized image.
             * We need to overwrite the existing image.
             * 
             */
            public function sync_image($row_img, $local_file){
                add_filter( 'upload_mimes', array($this, 'add_webp_mime'), 10, 2 );
                do_action( 'sm:sync::syncFile', $row_img->src . '.webp', $local_file, true);

            }
            
            /**
             * Sync the image when Lite Speed plugin pull the optimized image.
             * We need to overwrite the existing image.
             * 
             */
            public function sync_attachment($metadata, $attachment_id, $force = false, $args = array()){
                add_filter( 'upload_mimes', array($this, 'add_webp_mime'), 10, 2 );
                $args = wp_parse_args($args, array(
                    'no_thumb' => false,
                ));

                $upload_dir = wp_upload_dir();
                $fullsizepath = wp_normalize_path( get_attached_file( $attachment_id ) );
                $gs_name = str_replace( wp_normalize_path(trailingslashit( $upload_dir[ 'basedir' ] )), '', wp_normalize_path($fullsizepath) );
                $gs_name = apply_filters( 'wp_stateless_file_name', $gs_name);
                do_action( 'sm:sync::syncFile', $gs_name . '.webp', $fullsizepath . '.webp', true);

                
                $mediaPath = trim( dirname( $gs_name ), '\/\\' );

                /**
                 * @see https://github.com/wpCloud/wp-stateless/issues/343
                 **/
                $mediaPath = $mediaPath === '.' ? '' : $mediaPath;
                
                /* Now we go through all available image sizes and upload them to Google Storage */
                if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) && $args['no_thumb'] != true ) {
                    $path = wp_normalize_path( dirname( get_attached_file( $attachment_id ) ) );

                    foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {
                        $fullsizepath = wp_normalize_path( $path . '/' . $data[ 'file' ] );
                        $gs_name = trim($mediaPath . '/' . $data[ 'file' ], '/');
                        do_action( 'sm:sync::syncFile', $gs_name . '.webp', $fullsizepath . '.webp', true);
                    }
                }


            }

            /**
             * Bypassing the is_internal_file check on LiteSpeed Cache.
             * That check fails because we are replacing URL with GCS URL.
             * So we need to override it with filter.
             * 
             * @todo maybe we can add some validation.
             */
            public function litespeed_media_check_img($return, $url){
                $image_host = ud_get_stateless_media()->get_gs_host();
                if(strpos($url, $image_host) === 0){
                    return true;
                }
                return $return;
            }

            /**
             * add_webp_mime
             * 
             */
            public function add_webp_mime($t, $user){
                $t['webp'] = 'image/webp';
                return $t;
            }

        }

    }

}
