<?php
/**
 * Bootstrap
 *
 * @since 0.2.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Bootstrap' ) ) {

    final class Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {

      /**
       * Google Storage Client
       * Use $this->get_client()
       *
       * @var \wpCloud\StatelessMedia\GS_CLient
       */
      private $client;

      /**
       * Plugin core version.
       *
       * @static
       * @property $version
       * @type {Object}
       */
      public static $version = '1.9.1';

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \wpCloud\StatelessMedia\Bootstrap object
       */
      protected static $instance = null;

      /**
       * Instantaite class.
       */
      public function init() {

        /**
         * Register SM metaboxes
         */
        add_action( 'admin_init', array( $this, 'register_metaboxes' ) );

        /**
         * Add custom actions to media rows
         */
        add_filter( 'media_row_actions', array( $this, 'add_custom_row_actions' ), 10, 3 );

        /**
         * Handle switch blog properly.
         */
        add_action( 'switch_blog', array( $this, 'on_switch_blog' ), 10, 2 );

        /**
         * Init AJAX jobs
         */
        new Ajax();

        /**
         * Maybe Upgrade current Version
         */
        Upgrader::call( $this->args[ 'version' ] );

        /**
         * Load WP-CLI Commands
         */
        if( defined( 'WP_CLI' ) && WP_CLI ) {
          include_once($this->path('lib/cli/class-sm-cli-command.php', 'dir'));
        }

        $this->is_network_detected();

        /**
         * Define settings and UI.
         *
         * Example:
         *
         * Get option
         * $this->get( 'sm.client_id' )
         *
         * Manually Update/Add option
         * $this->set( 'sm.client_id', 'zxcvv12adffse' );
         */
        $this->settings = new Settings();

        /**
         * Add scripts
         */
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        /**
         * Hashify file name if option is enabled
         */
        if ( $this->get( 'sm.hashify_file_name' ) == 'true' ) {
          add_filter('sanitize_file_name', array( $this, 'randomize_filename' ), 10);
        }

        /* Initialize plugin only if Mode is not 'disabled'. */
        if ( $this->get( 'sm.mode' ) !== 'disabled' ) {

          /**
           * Override Cache Control is option is enabled
           */
          if ( $this->get( 'sm.override_cache_control' ) == 'true' ) {
            add_filter( 'sm:item:cacheControl', array( $this, 'override_cache_control' ) );
          }

          /**
           * Determine if we have issues with connection to Google Storage Bucket
           * if SM is not disabled.
           */
          $is_connected = $this->is_connected_to_gs();

          if ( is_wp_error( $is_connected ) ) {
            $this->errors->add( $is_connected->get_error_message() );
          }

          /** Temporary fix to WP 4.4 srcset feature **/
          add_filter( 'max_srcset_image_width', create_function( '', 'return 1;' ) );

          /**
           * Carry on only if we do not have errors.
           */
          if( !$this->has_errors() ) {

            if( $this->get( 'sm.mode' ) === 'cdn' ) {
              add_filter( 'wp_get_attachment_image_attributes', array( $this, 'wp_get_attachment_image_attributes' ), 20, 3 );
              add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 20, 2 );
              add_filter( 'attachment_url_to_postid', array( $this, 'attachment_url_to_postid' ), 20, 2 );

              if ( $this->get( 'sm.body_rewrite' ) == 'true' ) {
                add_filter( 'the_content', array( $this, 'the_content_filter' ) );
              }
            }

            if ( $root_dir = $this->get( 'sm.root_dir' ) ) {
              if ( trim( $root_dir ) !== '' ) {
                add_filter( 'wp_stateless_file_name', array( $this, 'handle_root_dir' ) );
              }
            }

            /**
             * Rewrite Image URLS
             */
            add_filter( 'image_downsize', array( $this, 'image_downsize' ), 99, 3 );

            /**
             * Extends metadata by adding GS information.
             */
            add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 10, 2 );

            /**
             * Add/Edit Media
             *
             * Once added or edited we can get into Attachment ID then get all image sizes and sync them with GS
             */
            add_filter( 'wp_generate_attachment_metadata', array( $this, 'add_media' ), 100, 2 );

            if ( $this->get( 'sm.on_fly' ) == 'true' ) {
              /**
               * Handle any other on fly generated media
               */
              add_filter('image_make_intermediate_size', array($this, 'handle_on_fly'));
            }

            if ( $this->get( 'sm.delete_remote' ) == 'true' ) {
              /**
               * On physical file deletion we remove any from GS
               */
              add_filter('delete_attachment', array($this, 'remove_media'));
            }
          }

        }

      }

      /**
       * Get new blog settings once switched blog.
       * @param $new_blog
       * @param $prev_blog_id
       */
      public function on_switch_blog( $new_blog, $prev_blog_id ) {
        $this->settings->refresh();
      }

      /**
       * @param $actions
       * @param $post
       * @param $detached
       * @return mixed
       */
      public function add_custom_row_actions( $actions, $post, $detached ) {

        if ( !current_user_can( 'upload_files' ) ) return $actions;

        if ( $post && 'attachment' == $post->post_type && 'image/' == substr( $post->post_mime_type, 0, 6 ) ) {
          $actions['sm_sync'] = '<a href="javascript:;" data-id="'.$post->ID.'" data-type="image" class="sm_inline_sync">' . __('Regenerate and Sync with GCS', ud_get_stateless_media()->domain) . '</a>';
        }

        if ( $post && 'attachment' == $post->post_type && 'image/' != substr( $post->post_mime_type, 0, 6 ) ) {
          $actions['sm_sync'] = '<a href="javascript:;" data-id="'.$post->ID.'" data-type="other" class="sm_inline_sync">' . __('Sync with GCS', ud_get_stateless_media()->domain) . '</a>';
        }

        return $actions;

      }

      /**
       * Register metaboxes
       */
      public function register_metaboxes() {
        add_meta_box(
          'sm-attachment-metabox',
          __( 'Google Cloud Storage', ud_get_stateless_media()->domain ),
          array($this, 'attachment_meta_box_callback'),
          'attachment',
          'side',
          'low'
        );
      }

      /**
       * @param $post
       */
      public function attachment_meta_box_callback( $post ) {
        ob_start();

        $sm_cloud = get_post_meta( $post->ID, 'sm_cloud', 1 );

        if ( is_array( $sm_cloud ) && !empty( $sm_cloud[ 'fileLink' ] ) ) { ?>

          <?php if( !empty( $sm_cloud[ 'cacheControl' ] ) ) { ?>
            <div class="misc-pub-cache-control hidden">
              <?php _e( 'Cache Control:', ud_get_stateless_media()->domain ); ?> <strong><span><?php echo $sm_cloud[ 'cacheControl' ]; ?></span> </strong>
            </div>
          <?php } ?>

          <div class="misc-pub-gs-file-link" style="margin-bottom: 15px;">
            <label>
              <?php _e( 'Storage Bucket URL:', ud_get_stateless_media()->domain ); ?> <a href="<?php echo $sm_cloud[ 'fileLink' ]; ?>" target="_blank" class="sm-view-link"><?php _e( '[view]' ); ?></a>
              <input type="text" class="widefat urlfield" readonly="readonly" value="<?php echo esc_attr($sm_cloud[ 'fileLink' ]); ?>" />
            </label>
          </div>

          <?php

          if ( !empty( $sm_cloud[ 'bucket' ] ) ) {
            ?>
            <div class="misc-pub-gs-bucket" style="margin-bottom: 15px;">
              <label>
                <?php _e( 'Storage Bucket:', ud_get_stateless_media()->domain ); ?>
                <input type="text" class="widefat urlfield" readonly="readonly" value="gs://<?php echo esc_attr($sm_cloud[ 'bucket' ]); ?>" />
              </label>
            </div>
            <?php
          }

          if ( current_user_can( 'upload_files' ) ) {
            if ( $post && 'attachment' == $post->post_type && 'image/' == substr( $post->post_mime_type, 0, 6 ) ) {
              ?>
              <a href="javascript:;" data-type="image" data-id="<?php echo $post->ID; ?>"
                 class="button-secondary sm_inline_sync"><?php _e('Regenerate and Sync with GCS', ud_get_stateless_media()->domain); ?></a>
              <?php
            }

            if ( $post && 'attachment' == $post->post_type && 'image/' != substr( $post->post_mime_type, 0, 6 ) ) {
              ?>
              <a href="javascript:;" data-type="other" data-id="<?php echo $post->ID; ?>"
                 class="button-secondary sm_inline_sync"><?php _e('Sync with GCS', ud_get_stateless_media()->domain); ?></a>
              <?php
            }
          }
        }

        echo apply_filters( 'sm::attachment::meta', ob_get_clean(), $post->ID );
      }

      /**
       * @param $current_path
       * @return string
       */
      public function handle_root_dir( $current_path ) {
        $root_dir = $this->get( 'sm.root_dir' );
        $root_dir = trim( $root_dir );

        if ( !empty( $root_dir ) ) {
          return $root_dir . $current_path;
        }

        return $current_path;
      }

      /**
       * @param $content
       * @return mixed
       */
      public function the_content_filter( $content ) {

        if ( $upload_data = wp_upload_dir() ) {

          if ( !empty( $upload_data['baseurl'] ) && !empty( $content ) ) {
            $baseurl = preg_replace('/https?:\/\//','',$upload_data['baseurl']);
            $root_dir = trim( $this->get( 'sm.root_dir' ) );
            $root_dir = !empty( $root_dir ) ? $root_dir : false;
            $content = preg_replace( '/(href|src)=(\'|")(https?:\/\/'.str_replace('/', '\/', $baseurl).')\/(.+?)(\.jpg|\.png|\.gif|\.jpeg)(\'|")/i',
                '$1=$2https://storage.googleapis.com/'.$this->get( 'sm.bucket' ).'/'.($root_dir?$root_dir:'').'$4$5$6', $content);
          }
        }

        return $content;
      }

      /**
       * Handle images on fly
       *
       * @param $file
       * @return mixed
       */
      public function handle_on_fly( $file ) {

        $client = ud_get_stateless_media()->get_client();
        $upload_dir = wp_upload_dir();

        $file_path = str_replace( trailingslashit( $upload_dir[ 'basedir' ] ), '', $file );
        $file_info = @getimagesize( $file );

        if ( $file_info ) {
          $_metadata = array(
            'width'  => $file_info[0],
            'height' => $file_info[1],
            'object-id' => 'unknown', // we really don't know it
            'source-id' => md5( $file.ud_get_stateless_media()->get( 'sm.bucket' ) ),
            'file-hash' => md5( $file )
          );
        }

        $client->add_media( apply_filters('sm:item:on_fly:before_add', array_filter( array(
          'name' => $file_path,
          'absolutePath' => wp_normalize_path( $file ),
          'cacheControl' => apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', $_metadata ),
          'contentDisposition' => null,
          'metadata' => $_metadata
        ) ) ) );

        return $file;
      }

      /**
       * @param $links
       * @param $file
       * @return mixed
       */
      public function plugin_action_links( $links, $file ) {

        if ($file == plugin_basename( dirname( __DIR__ ) . '/wp-stateless-media.php' ) ) {
          $settings_link = '<a href="'. '' .'">'.__( 'Settings' , 'ssd').'</a>';
          array_unshift( $links, $settings_link );
        }

        if ($file == plugin_basename( dirname( __DIR__ ) . '/wp-stateless.php' ) ) {
          $settings_link = '<a href="'. '' .'">'.__( 'Settings' , 'ssd').'</a>';
          array_unshift( $links, $settings_link );
        }

        return $links;
      }

      /**
       * Determines if plugin is loaded via mu-plugins
       * or Network Enabled.
       *
       * @author peshkov@UD
       */
      public function is_network_detected() {
        /* Plugin is loaded via mu-plugins. */

        if( strpos( Utility::normalize_path( $this->root_path ), Utility::normalize_path( WPMU_PLUGIN_DIR ) ) !== false ) {
          return true;
        }

        if( is_multisite() ) {
          /* Looks through network enabled plugins to see if our one is there. */
          foreach (wp_get_active_network_plugins() as $path) {
            if ($this->boot_file == $path) {
              return true;
            }
          }
        }
        return false;
      }

      /**
       *
       * @todo: it should not be loaded everywhere. peshkov@UD
       */
      public function admin_enqueue_scripts( $hook ) {

        wp_enqueue_style( 'wp-stateless', $this->path( 'static/styles/wp-stateless.css', 'url'  ), array(), self::$version );

        switch( $hook ) {

          case 'upload.php':

            wp_enqueue_script( 'wp-stateless-uploads-js', $this->path( 'static/scripts/wp-stateless-uploads.js', 'url'  ), array( 'jquery' ), self::$version );

            break;

          case 'post.php':

            global $post;

            if ( $post->post_type == 'attachment' ) {
              wp_enqueue_script( 'wp-stateless-uploads-js', $this->path( 'static/scripts/wp-stateless-uploads.js', 'url'  ), array( 'jquery' ), self::$version );
            }

            break;

          default: break;
        }

      }

      /**
       * Add Attributes to media HTML
       *
       * @author potanin@UD
       * @param $attr
       * @param $attachment
       * @param $size
       * @return mixed
       */
      public function wp_get_attachment_image_attributes( $attr, $attachment, $size ) {

        $sm_cloud = get_post_meta( $attachment->ID, 'sm_cloud', true );
        if( is_array( $sm_cloud ) && !empty( $sm_cloud[ 'name' ] ) ) {
          $attr[ 'class' ] = $attr[ 'class' ] . ' wp-stateless-item';
          $attr[ 'data-image-size' ] = is_array( $size ) ? implode( 'x', $size ) : $size;
          $attr[ 'data-stateless-media-bucket' ] = isset( $sm_cloud[ 'bucket' ] ) ? $sm_cloud[ 'bucket' ] : false;
          $attr[ 'data-stateless-media-name' ] = $sm_cloud[ 'name' ];
        }

        return $attr;

      }

      /**
       * Adds filter link to Media Library table.
       *
       * @param $views
       * @return mixed
       */
      public function views_upload( $views ) {
        $views['stateless'] = '<a href="#">' . __( 'Stateless Media' ) . '</a>';
        return $views;
      }

      /**
       * Replace media URL
       *
       * @param bool $false
       * @param integer $id
       * @param string $size
       * @return mixed $false
       */
      public function image_downsize( $false = false, $id, $size ) {

        if ( !isset( $this->client ) || !$this->client || is_wp_error( $this->client ) ) {
          return $false;
        }

        /**
         * Check if enabled
         */
        if ( $this->get( 'sm.mode' ) !== 'cdn' ) {
          return $false;
        }

        /** Start determine remote file */
        $img_url = wp_get_attachment_url($id);
        $meta = wp_get_attachment_metadata($id);
        $width = $height = 0;
        $is_intermediate = false;

        //** try for a new style intermediate size */
        if ( $intermediate = image_get_intermediate_size( $id, $size ) ) {
          $img_url = !empty( $intermediate['gs_link'] ) ? $intermediate['gs_link'] : $intermediate['url'];
          $width = $intermediate['width'];
          $height = $intermediate['height'];
          $is_intermediate = true;
        }
        //die( '<pre>' . print_r( $intermediate, true ) . '</pre>' );
        if ( !$width && !$height && isset( $meta['width'], $meta['height'] ) ) {

          //** any other type: use the real image */
          $width = $meta['width'];
          $height = $meta['height'];
        }


        if ( $img_url) {

          //** we have the actual image size, but might need to further constrain it if content_width is narrower */
          list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

          return array( $img_url, $width, $height, $is_intermediate );
        }


        /**
         * All other cases work as usually
         */
        return $false;

      }

      /**
       * Extends metadata by adding GS information.
       * Note: must not be called directly. It's used only on hook
       *
       * @action wp_get_attachment_metadata
       * @param $metadata
       * @param $attachment_id
       * @return $metadata
       */
      public function wp_get_attachment_metadata( $metadata, $attachment_id  ) {
        /* Determine if the media file has GS data at all. */
        $sm_cloud = get_post_meta( $attachment_id, 'sm_cloud', true );
        if( is_array( $sm_cloud ) && !empty( $sm_cloud[ 'fileLink' ] ) ) {
          $metadata[ 'gs_link' ] = $sm_cloud[ 'fileLink' ];
          $metadata[ 'gs_name' ] = isset( $sm_cloud[ 'name' ] ) ? $sm_cloud[ 'name' ] : false;
          $metadata[ 'gs_bucket' ] = isset( $sm_cloud[ 'bucket' ] ) ? $sm_cloud[ 'bucket' ] : false;
          if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {
            foreach( $metadata[ 'sizes' ] as $k => $v ) {
              if( !empty( $sm_cloud[ 'sizes' ][ $k ][ 'name' ] ) ) {
                $metadata['sizes'][$k]['gs_name'] = $sm_cloud[ 'sizes' ][ $k ][ 'name' ];
                $metadata['sizes'][$k]['gs_link'] = $sm_cloud[ 'sizes' ][ $k ][ 'fileLink' ];
              }
            }
          }
        }

        return $metadata;
      }

      /**
       * Returns client object
       * or WP_Error on failure.
       *
       * @author peshkov@UD
       * @return object $this->client. \wpCloud\StatelessMedia\GS_Client or \WP_Error
       */
      public function get_client() {

        if( null === $this->client ) {

          $key_json = get_site_option( 'sm_key_json' );
          if ( empty($key_json) ) {
            $key_json = $this->get( 'sm.key_json' );
          }

          /* Try to initialize GS Client */
          $this->client = GS_Client::get_instance( array(
            'bucket' => $this->get( 'sm.bucket' ),
            'key_json' => $key_json
          ) );
        }

        return $this->client;

      }

      /**
       * Determines if we can connect to Google Storage Bucket.
       *
       * @author peshkov@UD
       */
      public function is_connected_to_gs() {

        //$trnst = get_transient( 'sm::is_connected_to_gs' );

        if ( empty($trnst) || false === $trnst || !isset( $trnst[ 'hash' ] ) || $trnst[ 'hash' ] != md5( serialize( $this->get( 'sm' ) ) ) ) {
          $trnst = array(
            'success' => 'true',
            'error' => '',
            'hash' => md5( serialize( $this->get( 'sm' ) ) ),
          );
          $client = $this->get_client();
          if ( is_wp_error( $client ) ) {
            $trnst[ 'success' ] = 'false';
            $trnst[ 'error' ] = $client->get_error_message();
          } else {
            if( !$client->is_connected() ) {
              $trnst[ 'success' ] = 'false';
              $trnst[ 'error' ] = sprintf( __( 'Could not connect to Google Storage bucket. Please, be sure that bucket with name <b>%s</b> exists.', $this->domain ), $this->get( 'sm.bucket' ) );
            }
          }
          set_transient( 'sm::is_connected_to_gs', $trnst, 24 * HOUR_IN_SECONDS );
        }

        if( isset( $trnst[ 'success' ] ) && $trnst[ 'success' ] == 'false' ) {
          return new \WP_Error( 'error', ( !empty( $trnst[ 'error' ] ) ? $trnst[ 'error' ] : __( 'There is an Error on connection to Google Storage.', $this->domain ) ) );
        }

        return true;
      }

      /**
       * Flush all plugin transients
       *
       */
      public function flush_transients() {
        delete_transient( 'sm::is_connected_to_gs' );
      }

      /**
       * Plugin Activation
       *
       */
      public function activate() {}

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

      /**
       * Filter for wp_get_attachment_url();
       *
       * @param string $url
       * @param string $post_id
       * @return mixed|null|string
       */
      public function wp_get_attachment_url( $url = '', $post_id = '' ) {
        $sm_cloud = get_post_meta( $post_id, 'sm_cloud', 1 );
        if( is_array( $sm_cloud ) && !empty( $sm_cloud[ 'fileLink' ] ) ) {
          return strpos( $sm_cloud[ 'fileLink' ], 'https://' ) === false ? ( 'https:' . $sm_cloud[ 'fileLink' ] ) : $sm_cloud[ 'fileLink' ];
        }
        return $url;
      }

      /**
       * Filter for attachment_url_to_postid()
       * 
       * @param int|false $post_id originally found post ID (or false if not found)
       * @param string $url the URL to find the post ID for
       * @return int|false found post ID from cloud storage URL
       */
      public function attachment_url_to_postid( $post_id, $url ) {
        global $wpdb;

        if ( ! $post_id ) {
          $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sm_cloud' AND meta_value LIKE '%s'";

          $post_id = $wpdb->get_var( $wpdb->prepare( $query, '%' . $url . '%' ) );
        }

        return $post_id;
      }

      /**
       * Change Upload BaseURL when CDN Used.
       *
       * @param $data
       * @return mixed
       */
      public function upload_dir( $data ) {

        $data[ 'baseurl' ] = '//storage.googleapis.com/' . ( $this->get( 'sm.bucket' ) );
        $data[ 'url' ] = $data[ 'baseurl' ] . $data[ 'subdir' ];

        return $data;

      }

      /**
       * Determine if Utility class contains missed function
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       * @param $name
       * @param $arguments
       * @return mixed|null
       */
      public function __call( $name, $arguments ) {
        if( is_callable( array( "wpCloud\\StatelessMedia\\Utility", $name ) ) ) {
          return call_user_func_array( array( "wpCloud\\StatelessMedia\\Utility", $name ), $arguments );
        } else {
          return NULL;
        }
      }

    }

  }

}
