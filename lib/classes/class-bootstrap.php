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
      public static $version = '2.1';

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
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        
        //** Define our Admin Notices handler object */
        $this->errors = new Errors( array_merge( $args, array(
          'type' => $this->type
        ) ) );

        // Initialize compatibility modules.
        add_action( 'plugins_loaded', function(){
          new Module();
        });
      }

      /**
       * Instantiate class.
       */
      public function init() {

        /**
         * Copied from wp-property
         * Duplicates UsabilityDynamics\WP\Bootstrap_Plugin::load_textdomain();
         *
         * There is a bug with localization in lib-wp-bootstrap 1.1.3 and lower.
         * So we load textdomain here again, in case old version lib-wp-bootstrap is being loaded
         * by another plugin.
         *
         * @since 1.9.1
         */
        load_plugin_textdomain($this->domain, false, dirname(plugin_basename($this->boot_file)) . '/static/languages/');
        
        // Parse feature falgs, set constants.
        $this->parse_feature_flags();

        new SyncNonMedia();
        
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

        // Invoke REST API
        add_action( 'rest_api_init', array( $this, 'api_init' ) );

        /**
         * Register SM metaboxes
         */
        add_action( 'admin_init', array( $this, 'register_metaboxes' ) );

        /**
         * Init hook
         */
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        /**
         * Add custom actions to media rows
         */
        add_filter( 'media_row_actions', array( $this, 'add_custom_row_actions' ), 10, 3 );

        /**
         * Handle switch blog properly.
         */
        add_action( 'switch_blog', array( $this, 'on_switch_blog' ), 10, 2 );

        /**
         * Filter for getting stateless settings
         */
        add_filter( 'stateless::get_settings', array( $this, 'get_settings' ), 10);

        /**
         * Init AJAX jobs
         */
        new Ajax();

        /**
         * Load WP-CLI Commands
         */
        if( defined( 'WP_CLI' ) && WP_CLI ) {
          include_once($this->path('lib/cli/class-sm-cli-command.php', 'dir'));
        }

        $this->is_network_detected();

        /**
         * Add scripts
         */
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        /**
         * Hashify file name if option is enabled
         */
        if ( $this->get( 'sm.hashify_file_name' ) == 'true' ) {
          add_filter('sanitize_file_name', array( 'wpCloud\StatelessMedia\Utility', 'randomize_filename' ), 10);
        }

        /**
         * Delete table when blog is deleted.
         */
        add_action( 'wp_delete_site', array($this, 'wp_delete_site'));

        /* Initialize plugin only if Mode is not 'disabled'. */
        if ( $this->get( 'sm.mode' ) !== 'disabled' ) {

          /**
           * Override Cache Control is option is enabled
           */
          $cacheControl = trim($this->get( 'sm.cache_control' ));
          if ( !empty($cacheControl) ) {
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

          if ( $googleSDKVersionConflictError = get_transient( "wp_stateless_google_sdk_conflict" ) ) {
            $this->errors->add( $googleSDKVersionConflictError, 'warning' );
          }

          // To prevent fatal errors for users who use PHP 5.5 or less.
          if( version_compare(PHP_VERSION, '5.5', '<') ) {
            $this->errors->add( sprintf( __( 'The plugin requires PHP %s or higher. You current PHP version %s is too old.', ud_get_stateless_media()->domain ), '<b>5.5</b>', '<b>' . PHP_VERSION . '</b>' ) );
          }

          /** Temporary fix to WP 4.4 srcset feature **/
          //add_filter( 'max_srcset_image_width', function(){return 1;} );

          /**
           * Carry on only if we do not have errors.
           */
          if( !$this->has_errors() ) {

            if( $this->get( 'sm.mode' ) === 'cdn' || $this->get( 'sm.mode' ) === 'stateless' ) {
              add_filter( 'wp_get_attachment_image_attributes', array( $this, 'wp_get_attachment_image_attributes' ), 20, 3 );
              add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 20, 2 );
              add_filter( 'attachment_url_to_postid', array( $this, 'attachment_url_to_postid' ), 20, 2 );

              if ( $this->get( 'sm.body_rewrite' ) == 'true' ||  $this->get( 'sm.body_rewrite' ) == 'enable_editor' ) {
                add_filter( 'the_content', array( $this, 'the_content_filter' ), 99 );
              }

              if ( $this->get( 'sm.body_rewrite' ) == 'true' ||  $this->get( 'sm.body_rewrite' ) == 'enable_meta' ) {
                add_filter( 'get_post_metadata', array( $this, 'post_metadata_filter' ), 2, 4 );
              }

              add_filter( 'wp_stateless_bucket_link', array( $this, 'wp_stateless_bucket_link' ) );
            }

            add_filter( 'wp_stateless_file_name', array( $this, 'handle_root_dir' ) );

            /**
             * Rewrite Image URLS
             */
            add_filter( 'image_downsize', array( $this, 'image_downsize' ), 99, 3 );
            add_filter( 'wp_calculate_image_srcset', array($this, 'wp_calculate_image_srcset'), 10, 5 );

            /**
             * Extends metadata by adding GS information.
             */
            add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 10, 2 );

            /**
             * Add/Edit Media
             *
             * Once added or edited we can get into Attachment ID then get all image sizes and sync them with GS
             * We can't use this. That's prevent removing this filter.
             */
            add_filter( 'wp_update_attachment_metadata', array( 'wpCloud\StatelessMedia\Utility', 'add_media' ), 999, 2 );
            
            /**
             * Upload the full size image first.
             * 
             */
            if(!defined('WP_STATELESS_MEDIA_DISABLE_FULL_IMAGE_FIRST') || WP_STATELESS_MEDIA_DISABLE_FULL_IMAGE_FIRST != true){
              add_filter( 'intermediate_image_sizes_advanced', array( $this, 'before_intermediate_image_sizes' ), 10, 2 );
            }

            /**
             * Add Media
             *
             * Once added we can get into Attachment ID then get all image sizes and sync them with GS
             */
            // add_filter( 'wp_generate_attachment_metadata', array( $this, 'add_media' ), 100, 2 );

            if ( $this->get( 'sm.delete_remote' ) == 'true' ) {
              /**
               * On physical file deletion we remove any from GS
               */
              add_filter('delete_attachment', array($this, 'remove_media'));
            }

            // Trigger module initialization and registration.
            do_action('sm::module::init', $this->get( 'sm' ));
          }

        }
      }
      
      /**
       * Rebuild srcset from gs_link.
       * Using calculations returned from WordPress wp_calculate_image_srcset()
       * 
       */
      public function wp_calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id){
        if (empty($image_meta['gs_link'])) {
          $image_meta = wp_get_attachment_metadata($attachment_id);
        }

        if(is_array($sources) && !empty( $image_meta['gs_link'] )){
          $gs_name = $image_meta['gs_name'];
          // getting position of root_dir in gs_name.
          $root_dir_pos = strpos($gs_name, $image_meta['file']);
          // removing rood_dir from gs_name so we can compare to replace url with gs_link. 
          if($root_dir_pos !== false){
            $gs_name = substr($gs_name, $root_dir_pos);
          }

          if ( !isset($gs_name) || empty($gs_name) ) {
            return [];
          }

          foreach ($sources as $width => &$image) {

            // If srcset includes original image src, replace it
            if (substr_compare($image['url'], $gs_name, -strlen($gs_name)) === 0) {
              $image['url'] = $image_meta['gs_link'];
            // Replace all sizes
            } elseif (isset($image_meta['sizes']) && is_array($image_meta['sizes'])) {
              $found = false;
              foreach ($image_meta['sizes'] as $key => $meta) {
                if (!isset($meta['gs_name']) || empty($meta['gs_name'])) {
                  continue;
                }

                $thumb_gs_name = $meta['gs_name'];
                // removing rood_dir from gs_name
                if($root_dir_pos !== false){
                  $thumb_gs_name = substr($thumb_gs_name, $root_dir_pos);
                }

                if (substr_compare($image['url'], $thumb_gs_name, -strlen($thumb_gs_name)) === 0) {
                  $image['url'] = $meta['gs_link'];
                  $found = true;
                  break;
                }
              }

              // if no size found and mode is stateless and nothing to show for srcset item - unset that item
              if (!$found && $this->get( 'sm.mode' ) === 'stateless') {
                $image = null;
              }
            } else {
              // if mode is stateless and nothing to show for srcset item - unset that item
              if ( $this->get( 'sm.mode' ) === 'stateless' ) {
                $image = null;
              }
            }
          }
        }

        return array_filter( $sources );
      }

      /**
       * Return gs host.
       * If custom domain is set it's return bucket name as host,
       * else return storage.googleapis.com as host and append bucket name at the end.
       *
       * @param array $sm
       * @return mixed|void
       */
      public function get_gs_host($sm = array()) {
        $sm = $sm?$sm: $this->get( 'sm');
        $image_host = 'https://storage.googleapis.com/';
        $image_host .=  $sm['bucket'];

        $custom_domain = $sm['custom_domain'];
        $is_ssl = strpos($custom_domain, 'https://');
        $custom_domain = str_replace(array('http://', 'https://'), '', $custom_domain);
        $custom_domain = trim($custom_domain, '/');

        // checking whether the provided domain is valid.
        // if the custom domain is same as the bucket name
        // or the custom domain is using https.
        if ( !empty($sm['bucket']) && !empty($custom_domain) && $custom_domain !== 'storage.googleapis.com' && ( $is_ssl === 0 || $custom_domain == $sm['bucket'] ) ) {
          $image_host = $is_ssl === 0 ? 'https://' : 'http://';  // bucketname will be host
          $image_host .=  $custom_domain;
        }

        return apply_filters( 'get_gs_host', $image_host, $image_host, $sm['bucket'], $is_ssl, $sm );
      }

      /**
       * Filter for wp_stateless_bucket_link if custom domain is set.
       * It's get attachment url and remove "storage.googleapis.com" from url.
       * So that custom url can be used.
       *
       * @param $fileLink
       * @return mixed|string
       */
      public function wp_stateless_bucket_link($fileLink) {
        $bucketname = $this->get( 'sm.bucket' );
        $custom_domain = $this->get( 'sm.custom_domain' );
        $is_ssl = strpos($custom_domain, 'https://') === 0;
        $fileLink_is_ssl = strpos($fileLink, 'https://') === 0;
        $custom_domain = str_replace(array('http://', 'https://'), '', $custom_domain);
        $custom_domain = trim($custom_domain, '/');
        
        if ( $custom_domain !== 'storage.googleapis.com' && $custom_domain == $bucketname && strpos($fileLink, $bucketname) > 8 ) {
          $fileLink = ($is_ssl ? 'https://' : 'http://') . substr($fileLink, strpos($fileLink, $bucketname));
        }
        elseif( $custom_domain !== 'storage.googleapis.com' && $custom_domain == $bucketname && $fileLink_is_ssl !== $is_ssl){
          if($is_ssl)
            $fileLink = str_replace(array('http://', 'https://'), 'https://', $fileLink);
          else
            $fileLink = str_replace(array('http://', 'https://'), 'http://', $fileLink);
        }
        return $fileLink;
      }

      /**
       * Return settings page url.
       *
       * @param string $path
       * @return string
       */
      public function get_settings_page_url( $path = '' ) {
        $url = get_admin_url( get_current_blog_id(), ( is_network_admin() ? 'network/settings.php' : 'upload.php' ) );
        return $url . $path;
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
       * Get settings handler.
       * Filling array if some settings missing.
       *
       * @param $settings
       * @return
       */
      public function get_settings($settings) {

        $settings_list =  array(
            'mode',
            'body_rewrite',
            'body_rewrite_types',
            'bucket',
            'root_dir',
            'key_json',
            'cache_control',
            'delete_remote',
            'custom_domain',
            'organize_media',
            'hashify_file_name'
        );

        foreach ($settings_list as $setting) {

          /** If setting is already exist, just skip it */
          if( isset( $settings[ $setting ] ) ) {
            continue;
          }

          $value = $this->get( 'sm.' . $setting );

          /** Decode json to array */
          if( $value && is_string( $value ) && $setting === 'key_json' ) {
            $value = json_decode( $value, true );
            $setting = 'key';
          }

          $settings[ $setting ] = $value;
        }

        return $settings;
      }

      /**
       * Remove all settings.
       */
      public function reset($network = false) {
        $this->settings->reset($network);
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
       * Define REST API.
       *
       * // https://usabilitydynamics-sandbox-uds-io-stateless-testing.c.rabbit.ci/wp-json/wp-stateless/v1
       *
       * @author potanin@UD
       */
      public function api_init() {

        $route_namespace = 'wp-stateless/v1';
        $api_namespace = 'wpCloud\StatelessMedia\API';

        register_rest_route( $route_namespace, '/status', array(
          'methods' => 'GET',
          'callback' => array( $api_namespace, 'status' ),
        ) );

        register_rest_route( $route_namespace, '/jobs', array(
          'methods' => 'GET',
          'callback' => array( $api_namespace, 'jobs' ),
        ) );

        /**
         * Return stateless settings.
         *
         * Request parameter: none
         *
         * Response:
         *    ok: Whether API is up or not
         *    message: Describe what is done or error message on error.
         *    settings: array of stateless settings
         *
         */
        register_rest_route( $route_namespace, '/getSettings', array( 'methods' => 'GET', 'callback' => array( $api_namespace, 'getSettings' ), ) );

        /**
         * Essentially for scrolling through media library to build our index.
         *
         * Request parameter: none
         *
         * Response:
         *    ok: Whether API is up or not
         *    message: Describe what is done or error message on error.
         *    settings: array of media files
         *
         */
        register_rest_route( $route_namespace, '/getMediaLibrary', array( 'methods' => 'GET', 'callback' => array( $api_namespace, 'getMediaLibrary' ), ) );

        /**
         * Get detailed information of media file
         *
         * Request parameter: none
         *
         * Response:
         *    ok: Whether API is up or not
         *    message: Describe what is done or error message on error.
         *    settings: media file array
         *
         */
        register_rest_route( $route_namespace, '/getMediaItem', array( 'methods' => 'GET', 'callback' => array( $api_namespace, 'getMediaItem' ), ) );

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
        $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.

        $upload_dir = wp_upload_dir();
        $current_path = str_replace( wp_normalize_path( trailingslashit( $upload_dir[ 'basedir' ] ) ), '', wp_normalize_path( $current_path ) );
        $current_path = str_replace( wp_normalize_path( trailingslashit( $upload_dir[ 'baseurl' ] ) ), '', wp_normalize_path( $current_path ) );

        // skip adding root dir if it's already added.
        if ( !empty( $root_dir ) && strpos($current_path, $root_dir) !== 0 ) {
          return $root_dir . '/' . $current_path;
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
            $root_dir = trim( $this->get( 'sm.root_dir' ), '/ ' ); // Remove any forward slash and empty space.
            $root_dir = !empty( $root_dir ) ? $root_dir . '/' : false;
            $image_host = $this->get_gs_host();
            $file_ext = $this->replaceable_file_types();
            $content = preg_replace( '/(href|src)=(\'|")(https?:\/\/'.str_replace('/', '\/', $baseurl).')\/(.+?)('.$file_ext.')(\'|")/i',
                '$1=$2'.$image_host.'/'.($root_dir?$root_dir:'').'$4$5$6', $content);
          }
        }

        return $content;
      }

      /**
       * Return file types supported by File URL Replacement.
       *
       */
      public function replaceable_file_types(){
        $types = $this->get('sm.body_rewrite_types');

        // Removing extra space.
        $types = trim($types);
        $types = preg_replace("/\s{2,}/", ' ', $types);

        $types_arr = explode(' ', $types);
        return '\.' . implode('|\.', $types_arr);
      }

      /**
       * Copied from https://developer.wordpress.org/reference/functions/get_metadata/
       * 
       * @param $value null unless other filter hooked in this function.
       * @param $object_id post id
       * @param $meta_key 
       * @param $single
       * @return mixed
       */
      public function post_metadata_filter($value, $object_id, $meta_key, $single){
        if(empty($value)){
          $meta_type = 'post';
          $transient_key = "stateless_{$meta_type}_meta";
          
          $meta_cache = wp_cache_get($object_id, $transient_key);
          if(empty($meta_cache)){
            $meta_cache = wp_cache_get($object_id, $meta_type . '_meta');

            if ( !$meta_cache ) {
              $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
              $meta_cache = $meta_cache[$object_id];
            }
            
            foreach($meta_cache as $key => $meta){
              $meta_cache[$key] = array_map('maybe_unserialize', $meta_cache[$key]);
            }

            $meta_cache = $this->convert_to_gs_link($meta_cache);
            wp_cache_set($object_id, $meta_cache, $transient_key);
          }
          
          if ( ! $meta_key ) {
            return $meta_cache;
          }
          
          if ( isset($meta_cache[$meta_key]) ) {
            return $meta_cache[$meta_key];
          }
          
          // in case no metadata is found return what was passed in $value. 
          // $value most of the time is null.
          return $value;
        }

        return $this->convert_to_gs_link($value);

      }

      /**
       * Replace all image link with gs link and return only if meta modified.
       *
       * @param $meta
       * @return mixed or null when not changed.
       */
      public function convert_to_gs_link($meta){
        $updated = $meta;
        if ( $meta && $upload_data = wp_upload_dir() ) {
          if ( !empty( $upload_data['baseurl'] ) && !empty( $meta ) ) {
            $baseurl = preg_replace('/https?:\/\//','',$upload_data['baseurl']);
            $root_dir = trim( $this->get( 'sm.root_dir' ), '/ ' ); // Remove any forward slash and empty space.
            $root_dir = !empty( $root_dir ) ? $root_dir . '/': false;
            $image_host = $this->get_gs_host().'/'.($root_dir?$root_dir:'');
            $file_ext = $this->replaceable_file_types();
            $updated = $this->_convert_to_gs_link($meta, $image_host, $baseurl, $file_ext);
          }
        }

        if($updated == $meta){
          return null; // Not changed.
        }
        return $updated;
      }

      /**
       * Replace all image link with gs link
       *
       * @param $meta
       * @return mixed
       */
      public function _convert_to_gs_link($meta, $image_host, $baseurl, $file_ext){
        if(is_array($meta)){
          foreach ($meta as $key => $value) {
            $meta[$key] = $this->_convert_to_gs_link($value, $image_host, $baseurl, $file_ext);
          }
          return $meta;
        } elseif (is_object($meta) && $meta instanceof \stdClass ) {
          foreach (get_object_vars($meta) as $key => $value) {
            $meta->{$key} = $this->_convert_to_gs_link($value, $image_host, $baseurl, $file_ext);
          }
          return $meta;
        } elseif(is_string($meta)){
          return preg_replace( '/(https?:\/\/'.str_replace('/', '\/', $baseurl).')\/(.+?)(' . $file_ext . ')/i', $image_host.'$2$3', $meta);
        }

        return $meta;
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
            // Trying again using readlink in case it's a symlink file.
            // boot_file is already solved.
            // wp_normalize_path is helpfull in windows.
            if (wp_normalize_path($this->boot_file) == wp_normalize_path($path) || (is_link($path) AND $this->boot_file == readlink($path))) {
              return true;
            }
          }
        }
        return false;
      }

      /**
       * Initialization.
       * Register scripts and styles
       */
      public function admin_init() {
        $this->show_notice_stateless_cache_busting();
        wp_register_style( 'wp-stateless', $this->path( 'static/styles/wp-stateless.css', 'url'  ), array(), self::$version );

        /* Attachment or upload page */
        wp_register_script( 'wp-stateless-uploads-js', $this->path( 'static/scripts/wp-stateless-uploads.js', 'url'  ), array( 'jquery' ), self::$version );

        /* Setup wizard styles and scripts. */
        wp_register_style( 'wp-stateless-bootstrap', $this->path( 'static/styles/bootstrap.min.css', 'url'  ), array(), '3.3.7' );
        wp_register_style( 'bootstrap-grid-v4', $this->path( 'static/styles/bootstrap-grid.min.css', 'url'  ), array(), '3.3.7' );
        wp_register_style( 'wp-stateless-setup-wizard', $this->path( 'static/styles/wp-stateless-setup-wizard.css', 'url'  ), array(), self::$version );

        wp_register_script( 'async.min', ud_get_stateless_media()->path( 'static/scripts/async.js', 'url'  ), array(), ud_get_stateless_media()->version );
        wp_register_script( 'jquery.history', ud_get_stateless_media()->path( 'static/scripts/jquery.history.js', 'url'  ), array( 'jquery' ), ud_get_stateless_media()->version, true );
        wp_register_script( 'wp-stateless-validation', ud_get_stateless_media()->path( 'static/scripts/jquery.validation.js', 'url'  ), array( 'jquery' ), ud_get_stateless_media()->version, true );
        wp_register_script( 'wp-stateless-loading', ud_get_stateless_media()->path( 'static/scripts/jquery.loading.js', 'url'  ), array( 'jquery' ), ud_get_stateless_media()->version, true );
        wp_register_script( 'wp-stateless-comboBox', ud_get_stateless_media()->path( 'static/scripts/jquery.wp-stateless-combo-box.js', 'url'  ), array( 'jquery' ), ud_get_stateless_media()->version, true );
        wp_register_script( 'wp-stateless-setup', ud_get_stateless_media()->path( 'static/scripts/wp-stateless-setup.js', 'url'  ), array( 'jquery-ui-core', 'wp-api', 'jquery.history' ), ud_get_stateless_media()->version, true );
        wp_localize_script( 'wp-stateless-setup', 'stateless_l10n', $this->get_l10n_data() );

        wp_register_script( 'wp-stateless-setup-wizard-js', ud_get_stateless_media()->path( 'static/scripts/wp-stateless-setup-wizard.js', 'url'  ), array( 'jquery', 'wp-api', 'async.min', 'wp-stateless-setup', 'wp-stateless-comboBox', 'wp-stateless-validation', 'wp-stateless-loading' ), ud_get_stateless_media()->version, true );


        /* Stateless settings page */
        wp_register_script( 'wp-stateless-settings', ud_get_stateless_media()->path( 'static/scripts/wp-stateless-settings.js', 'url'  ), array(), ud_get_stateless_media()->version );
        wp_localize_script( 'wp-stateless-settings', 'stateless_l10n', $this->get_l10n_data() );
        
        wp_register_style( 'wp-stateless-settings', $this->path( 'static/styles/wp-stateless-settings.css', 'url'  ), array(), self::$version );

        // Sync tab
        if ( wp_script_is( 'jquery-ui-widget', 'registered' ) ){
          wp_register_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path('static/scripts/jquery-ui/jquery.ui.progressbar.min.js', 'url'), array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.8.6' );
        }
        else{
          wp_register_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/jquery.ui.progressbar.min.1.7.2.js', 'url' ), array( 'jquery-ui-core' ), '1.7.2' );
        }
        wp_register_script( 'wp-stateless-angular', ud_get_stateless_media()->path( 'static/scripts/angular.min.js', 'url' ), array(), '1.5.0', true );
        wp_register_script( 'wp-stateless', ud_get_stateless_media()->path( 'static/scripts/wp-stateless.js', 'url'  ), array( 'jquery-ui-core', 'wp-stateless-settings' ), ud_get_stateless_media()->version, true );
        
        wp_localize_script( 'wp-stateless', 'stateless_l10n', $this->get_l10n_data() );
        wp_localize_script('wp-stateless', 'wp_stateless_configs', array(
          'WP_DEBUG' => defined('WP_DEBUG') ? WP_DEBUG : false,
        ));
        
        $settings = ud_get_stateless_media()->get('sm');
        if(defined('WP_STATELESS_MEDIA_JSON_KEY') && WP_STATELESS_MEDIA_JSON_KEY){
          $settings['key_json'] = "Currently configured via a constant.";
        }
        wp_localize_script('wp-stateless', 'wp_stateless_settings', $settings);
        wp_localize_script('wp-stateless', 'wp_stateless_compatibility', Module::get_modules());
        wp_register_style( 'jquery-ui-regenthumbs', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/redmond/jquery-ui-1.7.2.custom.css', 'url' ), array(), '1.7.2' );

      }

      /**
       * Get_l10n_data
       *
       * @param string $value
       * @return mixed
       */
      public function get_l10n_data($value=''){
        include ud_get_stateless_media()->path( 'l10n.php', 'dir');
        return $l10n;
      }

      /**
       * Admin Scripts
       *
       * @param $hook
       */
      public function admin_enqueue_scripts( $hook ) {

        switch( $hook ) {

          case 'options-media.php':
            //wp_enqueue_script( 'wp-api' );

            wp_enqueue_style( 'wp-stateless');
            wp_enqueue_script( 'wp-stateless-setup' );
          break;

          case 'upload.php':

            wp_enqueue_style( 'wp-stateless');
            wp_enqueue_script( 'wp-stateless-uploads-js' );

            break;

          case 'post.php':

            global $post;

            if ( $post->post_type == 'attachment' ) {
              wp_enqueue_style( 'wp-stateless');
              wp_enqueue_script( 'wp-stateless-uploads-js' );
            }

            break;

          case 'media_page_stateless-setup':
          case 'settings_page_stateless-setup':
            wp_enqueue_style( 'wp-stateless');
            wp_enqueue_style( 'wp-stateless-bootstrap' );
            wp_enqueue_style( 'wp-stateless-setup-wizard' );

            wp_enqueue_script( 'async.min' );
            wp_enqueue_script( 'jquery.history' );
            wp_enqueue_script( 'wp-stateless-validation' );
            wp_enqueue_script( 'wp-stateless-loading' );
            wp_enqueue_script( 'wp-stateless-comboBox' );
            wp_enqueue_script( 'wp-stateless-setup' );
            wp_enqueue_script( 'wp-stateless-setup-wizard-js' );
            break;
          case 'media_page_stateless-settings':
          case 'settings_page_stateless-settings':
            wp_enqueue_style( 'wp-stateless');
            wp_enqueue_script( 'wp-stateless-settings' );
            wp_enqueue_style( 'bootstrap-grid-v4' );
            wp_enqueue_style( 'wp-stateless-settings' );
            
            // Sync tab
            wp_enqueue_script( 'jquery-ui-progressbar' );
            wp_enqueue_script( 'wp-stateless-angular' );
            wp_enqueue_script( 'wp-stateless' );
            wp_enqueue_style( 'jquery-ui-regenthumbs' );

            $data = array(
                    'key' => 'stateless-cache-busting',
                    'class' => 'notice',
                    'title' => sprintf( __( "Stateless mode enables and requires the Cache-Busting option.", ud_get_stateless_media()->domain ) ),
                    'message' => sprintf( __("WordPress looks at local files to prevent files with the same filenames. 
                                          Since Stateless mode bypasses this check, there is a potential for files to be stored with the same file name. We enforce the Cache-Busting option to prevent this. 
                                          Override with the <a href='%s' target='_blank'>%s</a> constant.", ud_get_stateless_media()->domain),"https://wp-stateless.github.io/docs/constants/#wp_stateless_media_cache_busting", "WP_STATELESS_MEDIA_CACHE_BUSTING" ),
                  );
            echo "<script id='template-stateless-cache-busting' type='text/html'>";
            include ud_get_stateless_media()->path( '/static/views/error-notice.php', 'dir' );
            echo "</script>";
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
      public function wp_get_attachment_image_attributes( $attr, $attachment, $size = null ) {

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
        if ( $this->get( 'sm.mode' ) !== 'cdn' && $this->get( 'sm.mode' ) !== 'stateless' ) {
          return $false;
        }

        /** Start determine remote file */
        $img_url = wp_get_attachment_url($id);
        $meta = wp_get_attachment_metadata($id);
        $width = $height = 0;
        $is_intermediate = false;

        //** try for a new style intermediate size */
        if ( $intermediate = image_get_intermediate_size( $id, $size ) ) {
          if(!empty( $intermediate['gs_link'] )){
            $img_url = $intermediate['gs_link'];
          }
          else if(!empty( $intermediate['url'] )){
            $img_url = $intermediate['url'];
          }
          else{
            $img_url = dirname($img_url) . $intermediate['file'];
          }
          
          $width = $intermediate['width'];
          $height = $intermediate['height'];
          $is_intermediate = true;
        }

        /**
         * maybe try to get images info from sm_cloud
         * this case may happen when no local files
         * @author korotkov@ud
         */
        if ( !$width && !$height ) {
          $sm_cloud = get_post_meta( $id, 'sm_cloud', true );
          if ( is_string($size) && !empty( $sm_cloud['sizes'] ) && !empty( $sm_cloud['sizes'][$size] ) ) {
            global $_wp_additional_image_sizes;

            $img_url = !empty( $sm_cloud['sizes'][$size]['fileLink'] ) ? $sm_cloud['sizes'][$size]['fileLink'] : $img_url;

            if ( !empty( $_wp_additional_image_sizes[ $size ] ) ) {
              $width = !empty( $_wp_additional_image_sizes[ $size ]['width'] ) ? $_wp_additional_image_sizes[ $size ]['width'] : $width;
              $height = !empty( $_wp_additional_image_sizes[ $size ]['height'] ) ? $_wp_additional_image_sizes[ $size ]['height'] : $height;
            }

            $is_intermediate = true;
          }
        }

        if ( !$width && !$height && isset( $meta['width'], $meta['height'] ) ) {

          //** any other type: use the real image */
          $width = $meta['width'];
          $height = $meta['height'];
        }


        if ( $img_url) {

          //** we have the actual image size, but might need to further constrain it if content_width is narrower */
          list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );
          $img_url = apply_filters('wp_stateless_bucket_link', $img_url);
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
       * @param $metadata
       * @param $attachment_id
       * @return array|mixed
       */
      public function wp_get_attachment_metadata( $metadata, $attachment_id  ) {
        /* Determine if the media file has GS data at all. */
        $sm_cloud = get_post_meta( $attachment_id, 'sm_cloud', true );
        // If metadata not passed the get metadata from post meta.
        if(empty($metadata)){
          $metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
        }

        if( is_array( $metadata ) && is_array( $sm_cloud ) && !empty( $sm_cloud[ 'fileLink' ] ) ) {
          $metadata[ 'gs_link' ] = apply_filters('wp_stateless_bucket_link', $sm_cloud[ 'fileLink' ]);
          $metadata[ 'gs_name' ] = isset( $sm_cloud[ 'name' ] ) ? $sm_cloud[ 'name' ] : false;
          $metadata[ 'gs_bucket' ] = isset( $sm_cloud[ 'bucket' ] ) ? $sm_cloud[ 'bucket' ] : false;
          if( !empty( $metadata[ 'sizes' ] ) && is_array( $metadata[ 'sizes' ] ) ) {
            foreach( $metadata[ 'sizes' ] as $k => $v ) {
              if( !empty( $sm_cloud[ 'sizes' ][ $k ][ 'name' ] ) ) {
                $metadata['sizes'][$k]['gs_name'] = $sm_cloud[ 'sizes' ][ $k ][ 'name' ];
                $metadata['sizes'][$k]['gs_link'] = apply_filters('wp_stateless_bucket_link', $sm_cloud[ 'sizes' ][ $k ][ 'fileLink' ]);
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

        $trnst = get_transient( 'sm::is_connected_to_gs' );

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
            $connected = $client->is_connected();
            if( $connected !== true ) {
              $trnst[ 'success' ] = 'false';
              $trnst[ 'error' ] = sprintf( __( 'Could not connect to Google Storage bucket. Please, be sure that bucket with name <b>%s</b> exists.', $this->domain ), $this->get( 'sm.bucket' ) );

              if( is_callable(array($connected, 'getHandlerContext')) && $handlerContext = $connected->getHandlerContext() ){
                if(!empty($handlerContext['error'])){
                  $handlerContext['error'];
                  $trnst[ 'error' ] = "Could not connect to Google Storage bucket. " . make_clickable($handlerContext['error']);
                }
              }

              if( is_callable(array($connected, 'getErrors')) && $error = $connected->getErrors() ){
                $error = reset($error);
                if($error['reason'] == 'accessNotConfigured')
                  $trnst[ 'error' ] = "Could not connect to Google Storage bucket. " . make_clickable($error['message']);
              }
            }
          }
          set_transient( 'sm::is_connected_to_gs', $trnst, 4 * HOUR_IN_SECONDS );
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
      public function activate() {
        add_action( 'activated_plugin', array($this, 'redirect_to_splash'), 99 );
        $this->run_upgrade_process();
      }

      /**
       * Run Install Process.
       * Triggered on plugins_loaded instead of register_activation_hook action.
       * Works on even manual plugin update.
       *
       * @author alim@UD
       */
      public function run_install_process()
      {
        // calling the upgrade function because it's same as this point for fresh install or updates.
        $this->run_upgrade_process();
      }

      /**
       * Run Upgrade Process:
       * Triggered on plugins_loaded instead of register_activation_hook action.
       * Works on even manual plugin update.
       *
       * @author alim@UD
       */
      public function run_upgrade_process()
      {
        // Creating database on new installation.
        $this->create_db();
        /**
         * Maybe Upgrade current Version
         */
        Upgrader::call( $this->args[ 'version' ] );
      }

      /**
       * Create database on plugin activation.
       * @param boolean $force - whether to create db even if option exists. For debug purpose only.
       */
      public function create_db($force = false) {
        global $wpdb;
        $sm_sync_db_version = get_option( 'sm_sync_db_version' );

        if( $sm_sync_db_version && $force == false ) {
          return;
        }

        $table_name = $wpdb->prefix . 'sm_sync';
        $charset_collate = $wpdb->get_charset_collate();

        // `expire` timestamp NULL DEFAULT NULL,
        $sql = "CREATE TABLE $table_name (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT ,
          `file` varchar(255) NOT NULL ,
          `status` varchar(10) NOT NULL ,
          PRIMARY KEY  (`id`)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option( 'sm_sync_db_version', $this->args[ 'version' ] );
      }

      /**
       * 
       * Delete table when blog is deleted.
       */
      public function wp_delete_site($old_site){
        global $wpdb;
        
        switch_to_blog( $old_site->id );
        $table_name = $wpdb->prefix . 'sm_sync';
        
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
        restore_current_blog();
      }

      /**
       * Redirect_to_splash
       *
       * @param string $plugin
       */
      public function redirect_to_splash($plugin =''){
        $this->settings = new Settings();

        if(defined( 'WP_CLI' ) || $this->settings->get('sm.key_json') || isset($_POST['checked']) && count($_POST['checked']) > 1){
          return;
        }
        
        if( 
          !$this->settings->get('sm.key_json') && 
          defined('WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT') && WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT == true && 
          defined('WP_STATELESS_MEDIA_HIDE_SETTINGS_PANEL') && WP_STATELESS_MEDIA_HIDE_SETTINGS_PANEL == true 
        ) {
          return;
        }
        
        if( !$this->settings->get('sm.key_json') && defined('WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT') && WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT == true ) {
          $url = $this->get_settings_page_url('?page=stateless-settings');
          exit( wp_redirect($url));
        }
        
        if( $plugin == plugin_basename( $this->boot_file ) ) {
          $url = $this->get_settings_page_url('?page=stateless-setup&step=splash-screen');
          if(json_decode($this->settings->get('sm.key_json'))){
            $url = $this->get_settings_page_url('?page=stateless-settings');
          }
          exit( wp_redirect($url));
        }
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

      /**
       * Show_notice_stateless_cache_busting
       *
       */
      public function show_notice_stateless_cache_busting(){
        $this->errors->add( array(
          'key' => 'stateless_cache_busting',
          'button' => 'View Settings',
          'button_link' => admin_url('upload.php?page=stateless-settings'),
          'title' => sprintf( __( "Stateless mode now requires the Cache-Busting option.", ud_get_stateless_media()->domain ) ),
          'message' => sprintf( __("WordPress looks at local files to prevent files with the same filenames. 
                                Since Stateless mode bypasses this check, there is a potential for files to be stored with the same file name. We enforce the Cache-Busting option to prevent this. 
                                Override with the <a href='%s' target='_blank'>%s</a> constant.", ud_get_stateless_media()->domain),"https://wp-stateless.github.io/docs/constants/#wp_stateless_media_cache_busting", "WP_STATELESS_MEDIA_CACHE_BUSTING" ),
        ), 'notice' );
      }

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
          $_url = parse_url($sm_cloud[ 'fileLink' ]);
          $url = !isset($_url['scheme']) ? ( 'https:' . $sm_cloud[ 'fileLink' ] ) : $sm_cloud[ 'fileLink' ];
          return apply_filters('wp_stateless_bucket_link', $url);
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
          $post_id = get_transient("stateless_url_to_postid_" . md5($url));

          if(defined('WP_STATELESS_LEGACY_URL_TO_POSTID')){
            // User can use this constant if they change the Bucket Folder (root_dir) after uploading image.
            // This can be little slow at first run.
            if(empty($post_id)){
              $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sm_cloud' AND meta_value LIKE '%s'";
              $post_id = $wpdb->get_var( $wpdb->prepare( $query, '%' . $url . '%' ) );            

              if($post_id){
                set_transient("stateless_url_to_postid_" . md5($url), $post_id);
              }
            }
            return $post_id;
          }

          if(empty($post_id)){
            $gs_base_url =  $this->get_gs_host();
            $gs_url =  $this->get_gs_host() . '/' . $this->get( 'sm.root_dir' );
            $site_url = parse_url($gs_url);
            $image_path = parse_url( $url );

            //force the protocols to match if needed
            if( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
              $url = str_replace( $image_path['scheme'], $site_url['scheme'], $url );
            }

            if( 0 === strpos( $url, $gs_url . '/' ) ) {
              $url = substr( $url, strlen( $gs_url . '/' ) );
            }
            else if( 0 === strpos( $url, $gs_base_url . '/' ) ) {
              // In case user added Bucket Folder (root_dir) after uploading image.
              $url = substr( $url, strlen( $gs_base_url . '/' ) );
            }

            $sql = $wpdb->prepare(
              "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
              $url
            );
            $post_id = $wpdb->get_var( $sql );

            if($post_id){
              set_transient("stateless_url_to_postid_" . md5($url), $post_id);
            }
          }
        }

        return $post_id;
      }

      /**
       * Upload the full size image first.
       * 
       */
      public function before_intermediate_image_sizes($sizes, $metadata = array()){
        if(empty($metadata)){
          return $sizes;
        }

        try{
          $attachment_id = attachment_url_to_postid($metadata['file']);
          $this->add_media(null, $attachment_id, false, array('no_thumb' => true));
        }
        catch(Exception $e){

        }

        return $sizes;
      }

      /**
       * Change Upload BaseURL when CDN Used.
       *
       * @param $data
       * @return mixed
       */
      public function upload_dir( $data ) {
        $data[ 'basedir' ] = $this->get_gs_host();
        $data[ 'baseurl' ] = $this->get_gs_host();
        $data[ 'url' ] = $data[ 'baseurl' ] . $data[ 'subdir' ];

        return $data;

      }

      /**
       * Set Feature Flag constants by parsing composer.json
       *
       * @todo Make sure settings from DB can override these.
       *
       * @author potanin@UD
       * @return array|mixed|null|object
       */
      public function parse_feature_flags( ) {

        try {

          $_raw = file_get_contents( Utility::normalize_path( $this->root_path ) . 'composer.json' );

          $_parsed = json_decode( $_raw  );

          // @todo Catch poorly formatted JSON.
          if( !is_object( $_parsed  ) ) {
            // throw new Error( "unable to parse."  );
          }

          foreach( (array) $_parsed->extra->featureFlags as $_feature ) {

            if( !defined( $_feature->constant  ) ) {
              define( $_feature->constant, $_feature->enabled );

              if( $_feature->enabled ) {
                Utility::log( 'Feature flag ' . $_feature->name . ', [' . $_feature->constant . '] enabled.' );
              } else {
                Utility::log( 'Feature flag ' . $_feature->name . ', [' . $_feature->constant . '] disabled.' );
              }
            }

          }

        } catch ( Exception $e ) {
          Utility::log( 'Unable to parse [composer.json] feature flags. Error: [' . $e->getMessage() . ']' );
          // echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        return isset( $_parsed ) ? $_parsed : null;

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
