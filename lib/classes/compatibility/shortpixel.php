<?php
/**
 * Plugin Name: ShortPixel Image Optimizer
 * Plugin URI: https://wordpress.org/plugins/shortpixel-image-optimiser/
 *
 * Compatibility Description: Ensures compatibility with ShortPixel Image Optimizer.
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\ShortPixel')) {
        
        class ShortPixel extends ICompatibility {

            protected $id = 'shortpixel';
            protected $title = 'ShortPixel Image Optimizer';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_SHORTPIXEL';
            protected $description = 'Ensures compatibility with ShortPixel Image Optimizer.';
            protected $plugin_file = 'shortpixel-image-optimiser/wp-shortpixel.php';

            public function module_init($sm){
                add_action( 'shortpixel_image_optimised', array($this, 'shortpixel_image_optimised') );
                add_filter( 'get_attached_file', array($this, 'fix_missing_file'), 10, 2 );
                add_action( 'shortpixel_before_restore_image', array($this, 'sync_backup_file') );
                add_action( 'shortpixel_after_restore_image', array($this, 'handleRestoreBackup') );
                add_action( 'admin_enqueue_scripts', array( $this, 'shortPixelJS') );
                // Sync from sync tab
                add_action( 'sm:synced::image', array( $this, 'sync_backup_file'), 10, 2 );
            }

            public function shortPixelJS(){
                $upload_dir = wp_upload_dir();
                $jsSuffix = '.min.js';

                if (defined('SHORTPIXEL_DEBUG') && SHORTPIXEL_DEBUG === true) {
                    $jsSuffix = '.js'; //use unminified versions for easier debugging
                }
                $dep = 'short-pixel' . $jsSuffix;
                wp_enqueue_script('stateless-short-pixel', ud_get_stateless_media()->path( 'lib/classes/compatibility/js/shortpixel.js', 'url'), array($dep), '', true);
                
                $image_host = ud_get_stateless_media()->get_gs_host();
                $bucketLink = apply_filters('wp_stateless_bucket_link', $image_host);
                
                wp_localize_script( 'stateless-short-pixel', '_stateless_short_pixel', array(
                    'baseurl' => $upload_dir[ 'baseurl' ],
                    'bucketLink' => $bucketLink,
                ));

            }


            /**
             * Try to restore images before compression
             *
             * @param $file
             * @param $attachment_id
             * @return mixed
             */
            public function fix_missing_file( $file, $attachment_id ) {

                /**
                 * If hook is triggered by ShortPixel
                 */
                if ( !$this->hook_from_shortpixel() ) return $file;

                /**
                 * If mode is stateless then we change it to cdn in order images not being deleted before optimization
                 * Remember that we changed mode via global var
                 */
                if ( ud_get_stateless_media()->get( 'sm.mode' ) == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'cdn' );
                    global $wp_stateless_shortpixel_mode;
                    $wp_stateless_shortpixel_mode = 'stateless';
                }

                $upload_dir = wp_upload_dir();
                $meta_data = wp_get_attachment_metadata( $attachment_id );

                /**
                 * Try to get all missing files from GCS
                 */
                if ( !file_exists( $file ) ) {
                    ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $file )), true, $file );
                }

                if ( !empty( $meta_data['sizes'] ) && is_array( $meta_data['sizes'] ) ) {
                    foreach( $meta_data['sizes'] as $image ) {
                        if ( !empty( $image['gs_name'] ) && !file_exists( $_file = trailingslashit( $upload_dir[ 'basedir' ] ).$image['gs_name'] ) ) {
                            ud_get_stateless_media()->get_client()->get_media( apply_filters( 'wp_stateless_file_name', $image['gs_name']), true, $_file );
                        }
                    }
                }

                return $file;
            }

            /**
             * Determine where we hook from
             * We need to do this only for something specific in shortpixel plugin
             *
             * @return bool
             */
            private function hook_from_shortpixel() {
                $call_stack = debug_backtrace();

                if ( !empty( $call_stack ) && is_array( $call_stack ) ) {
                    foreach( $call_stack as $step ) {
                        if ( $step['function'] == 'getURLsAndPATHs' && strpos( $step['file'], 'wp-short-pixel' ) ) {
                            return true;
                        }
                    }
                }

                return false;
            }

            /**
             * If image size not exist then upload it to GS.
             * 
             * $args = array(
             *      'thumbnail' => $thumbnail,
             *      'p_img_large' => $p_img_large,
             *   )
             */
            public function shortpixel_image_optimised($id){

                /**
                 * Restore stateless mode if needed
                 */
                global $wp_stateless_shortpixel_mode;
                if ( $wp_stateless_shortpixel_mode == 'stateless' ) {
                    ud_get_stateless_media()->set( 'sm.mode', 'stateless' );
                }

                $metadata = wp_get_attachment_metadata( $id );
                ud_get_stateless_media()->add_media( $metadata, $id, true );

                $this->sync_backup_file($id, $metadata);
            }

            /**
             * Sync backup image
             */
            public function sync_backup_file($id, $metadata = null){
                
                /* Get metadata in case if method is called directly. */
                if( empty($metadata) ) {
                    $metadata = wp_get_attachment_metadata( $id );
                }
                /* Now we go through all available image sizes and upload them to Google Storage */
                if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {

                    // Sync backup file with GCS
                    $file_path = get_attached_file( $id );
                    $fullSubDir = $this->returnSubDir($file_path);
                    $backup_path = SHORTPIXEL_BACKUP_FOLDER . '/' . $fullSubDir;

                    /**
                     * If mode is stateless then we change it to cdn in order images not being deleted before optimization
                     * Remember that we changed mode via global var
                     */
                    $wp_stateless_shortpixel_mode;
                    
                    if ( ud_get_stateless_media()->get( 'sm.mode' ) == 'stateless' ) {
                        ud_get_stateless_media()->set( 'sm.mode', 'cdn' );
                        $wp_stateless_shortpixel_mode = 'stateless';
                    }
                    foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {
                        $absolutePath = $backup_path . $data[ 'file' ];
                        $name = apply_filters( 'wp_stateless_file_name',  basename(SHORTPIXEL_BACKUP_FOLDER) . '/' . $fullSubDir . $data[ 'file' ]);
                        
                        do_action( 'sm:sync::syncFile', $name, $absolutePath, true);
                    }

                    if ( $wp_stateless_shortpixel_mode == 'stateless' ) {
                        ud_get_stateless_media()->set( 'sm.mode', 'stateless' );
                    }


                }
            }

            /**
             * return subdir for that particular attached file - if it's media library then last 3 path items, otherwise substract the uploads path
             * Has trailing directory separator (/)
             * 
             * @copied from shortpixel-image-optimiser\class\db\shortpixel-meta-facade.php
             * @param type $file
             * @return string
             */
            public function returnSubDir($file){
                $hp = wp_normalize_path(get_home_path());
                $file = wp_normalize_path($file);
                $sp__uploads = wp_upload_dir();
                if(strstr($file, $hp)) {
                    $path = str_replace( $hp, "", $file);
                } elseif( strstr($file, dirname( WP_CONTENT_DIR ))) { //in some situations the content dir is not inside the root, check this also (ex. single.shortpixel.com)
                    $path = str_replace( trailingslashit(dirname( WP_CONTENT_DIR )), "", $file);
                } elseif( (strstr(realpath($file), realpath($hp)))) {
                    $path = str_replace( realpath($hp), "", realpath($file));
                } elseif( strstr($file, trailingslashit(dirname(dirname( $sp__uploads['basedir'] )))) ) {
                    $path = str_replace( trailingslashit(dirname(dirname( $sp__uploads['basedir'] ))), "", $file);
                } else {
                    $path = (substr($file, 1));
                }
                $pathArr = explode('/', $path);
                unset($pathArr[count($pathArr) - 1]);
                return implode('/', $pathArr) . '/';
            }

            /**
             * Sync images after shortpixel restore them from backup.
             */
            public function handleRestoreBackup($attachmentID){
                $metadata = wp_get_attachment_metadata( $attachmentID );
                $this->add_media( $metadata, $attachmentID );
            }
            
            /**
             * Customized version of wpCloud\StatelessMedia\Utility::add_media()
             * to satisfied our need in restore backup
             * If a image isn't restored from backup then ignore it.
             */
            public static function add_media( $metadata, $attachment_id ) {
                $upload_dir = wp_upload_dir();

                $client = ud_get_stateless_media()->get_client();

                if( !is_wp_error( $client ) ) {

                    $fullsizepath = wp_normalize_path( get_attached_file( $attachment_id ) );
                    // Make non-images uploadable.
                    if( empty( $metadata['file'] ) && $attachment_id ) {
                        $metadata = array( "file" => str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', get_attached_file( $attachment_id ) ) );
                    }

                    $file = wp_normalize_path( $metadata[ 'file' ] );
                    $image_host = ud_get_stateless_media()->get_gs_host();
                    $bucketLink = apply_filters('wp_stateless_bucket_link', $image_host);
                    $_cacheControl = \wpCloud\StatelessMedia\Utility::getCacheControl( $attachment_id, $metadata, null );
                    $_contentDisposition = \wpCloud\StatelessMedia\Utility::getContentDisposition( $attachment_id, $metadata, null );
                    $_metadata = array(
                        "width" => isset( $metadata[ 'width' ] ) ? $metadata[ 'width' ] : null,
                        "height" => isset( $metadata[ 'height' ] )  ? $metadata[ 'height' ] : null,
                        'object-id' => $attachment_id,
                        'source-id' => md5( $attachment_id . ud_get_stateless_media()->get( 'sm.bucket' ) ),
                        'file-hash' => md5( $metadata[ 'file' ] )
                    );

                    if(file_exists($fullsizepath)){

                        /* Add default image */
                        $media = $client->add_media( $_mediaOptions = array_filter( array(
                            'force' => true,
                            'name' => $file,
                            'absolutePath' => wp_normalize_path( get_attached_file( $attachment_id ) ),
                            'cacheControl' => $_cacheControl,
                            'contentDisposition' => $_contentDisposition,
                            'mimeType' => get_post_mime_type( $attachment_id ),
                            'metadata' => $_metadata
                        ) ));

                        // Stateless mode: we don't need the local version.
                        if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                            unlink($fullsizepath);
                        }
                    }

                    /* Now we go through all available image sizes and upload them to Google Storage */
                    if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {

                        $path = wp_normalize_path( dirname( get_attached_file( $attachment_id ) ) );
                        $mediaPath = wp_normalize_path( trim( dirname( $metadata[ 'file' ] ), '\/\\' ) );

                        foreach( (array) $metadata[ 'sizes' ] as $image_size => $data ) {

                            $absolutePath = wp_normalize_path( $path . '/' . $data[ 'file' ] );

                            if( !file_exists($absolutePath)){
                                continue;
                            }
                            
                            /* Add 'image size' image */
                            $media = $client->add_media( array(
                                'force' => true,
                                'name' => $file_path = trim($mediaPath . '/' . $data[ 'file' ], '/'),
                                'absolutePath' => $absolutePath,
                                'cacheControl' => $_cacheControl,
                                'contentDisposition' => $_contentDisposition,
                                'mimeType' => $data[ 'mime-type' ],
                                'metadata' => array_merge( $_metadata, array(
                                    'width' => $data['width'],
                                    'height' => $data['height'],
                                    'child-of' => $attachment_id,
                                    'file-hash' => md5( $data[ 'file' ] )
                                ))
                            ));

                            /* Break if we have errors. */
                            if( !is_wp_error( $media ) ) {
                                // Stateless mode: we don't need the local version.
                                if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                                    unlink($absolutePath);
                                }
                            }

                        }

                    }

                }
            }
            // End add_media
        }

    }

}
