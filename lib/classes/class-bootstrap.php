<?php

/**
 * Bootstrap
 *
 * @since 0.2.0
 */

namespace wpCloud\StatelessMedia {

  use Google\Cloud\Storage\StorageClient;
  use Google\Auth\HttpHandler\HttpHandlerFactory;
  use wpCloud\StatelessMedia\Sync\FileSync;
  use wpCloud\StatelessMedia\Sync\ImageSync;
  use wpCloud\StatelessMedia\Sync\NonLibrarySync;

  if (!class_exists('wpCloud\StatelessMedia\Bootstrap')) {

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
      public static $version = '3.0';

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
       * @param $args
       * @author peshkov@UD
       */
      protected function __construct($args) {
        parent::__construct($args);

        /**
         * Add custom args to api ping request
         */
        add_filter('ud-api-client-ping-args', function ($args, $_, $__) {
          $args['multisite'] = is_multisite();
          $args['stateless_media'] = Utility::get_stateless_media_data_count();
          return $args;
        }, 10, 3);

        //** Define our Admin Notices handler object */
        $this->errors = new Errors(array_merge($args, array(
          'type' => $this->type
        )));

        // Initialize compatibility modules.
        add_action('plugins_loaded', function () {
          new Module();
        });

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
        $this->settings = new Settings($this);
      }

      /**
       * Prevent loading of textdomain
       */
      public function load_textdomain() {
      }

      /**
       * Instantiate class.
       */
      public function init() {
        // Parse feature falgs, set constants.
        $this->parse_feature_flags();
        $sm_mode = $this->get('sm.mode');

        new SyncNonMedia();

        ImageSync::instance();
        FileSync::instance();
        NonLibrarySync::instance();

        // Invoke REST API
        add_action('rest_api_init', array($this, 'api_init'));

        // Register meta boxes and fields for media edit page
        add_filter('rwmb_meta_boxes', array($this, 'attachment_meta_box_callback'));

        // Register meta boxes and fields for media modal page
        add_filter('attachment_fields_to_edit', array($this, 'attachment_modal_meta_box_callback'), 11, 2);

        /**
         * Init hook
         */
        add_action('admin_init', array($this, 'admin_init'));

        /**
         * Handle switch blog properly.
         */
        add_action('switch_blog', array($this, 'on_switch_blog'), 10, 2);

        /**
         * Filter for getting stateless settings
         */
        add_filter('stateless::get_settings', array($this, 'get_settings'), 10);

        /**
         * Init AJAX jobs
         */
        new Ajax();

        /**
         * Load WP-CLI Commands
         */
        if (defined('WP_CLI') && WP_CLI) {
          include_once($this->path('lib/cli/class-sm-cli-command.php', 'dir'));
        }

        /**
         * Add scripts
         */
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        /**
         * Delete table when blog is deleted.
         */
        add_action('wp_delete_site', array($this, 'wp_delete_site'));

        /**
         * To prevent fatal errors for users who use PHP 5.5 or less.
         */
        if (version_compare(PHP_VERSION, '5.5', '<')) {
          $this->errors->add(sprintf(__('The plugin requires PHP %s or higher. You current PHP version %s is too old.', ud_get_stateless_media()->domain), '<b>5.5</b>', '<b>' . PHP_VERSION . '</b>'));
        }

        /**
         * Add the currently processing nag
         */
        foreach (Utility::get_available_sync_classes() as $process) {
          if ($process->is_running()) {
            $this->errors->add([
              'title' => __('Media Library Synchronization Underway', ud_get_stateless_media()->domain),
              'message' => __('WP-Stateless is synchronizing your media library in accordance with the Mode setting. You can view progress or stop the process via the WP-Stateless Sync settings area.', ud_get_stateless_media()->domain),
              'button' => __('View Synchronization', ud_get_stateless_media()->domain),
              'button_link' => admin_url('upload.php?page=stateless-settings#stless_sync_tab'),
              'key' => 'processing-in-progress'
            ], 'message');
            break;
          }
        }

        /* Initialize plugin only if Mode is not 'disabled'. */
        if (($sm_mode !== 'disabled' && $sm_mode !== 'stateless') || ($sm_mode === 'stateless' && (wp_doing_ajax() || wp_doing_cron()))) {

          /**
           * Determine if we have issues with connection to Google Storage Bucket
           * if SM is not disabled.
           */
          $is_connected = $this->is_connected_to_gs();

          if (is_wp_error($is_connected)) {
            $this->errors->add($is_connected->get_error_message(), 'warning');
          }

          if ($googleSDKVersionConflictError = get_transient("wp_stateless_google_sdk_conflict")) {
            $this->errors->add($googleSDKVersionConflictError, 'warning');
          }

          /**
           * Carry on only if we do not have errors.
           */
          if (!$this->has_errors()) {

            if (in_array($sm_mode, array('cdn', 'ephemeral', 'stateless'))) {
              /**
               * init main filters
               */
              $this->_init_filters('main');
            }

            if ($sm_mode === 'ephemeral' || $sm_mode === 'stateless') {
              // Store attachment id in a static variable on 'intermediate_image_sizes_advanced' filter.
              // Utility::store_can_delete_attachment();
              if (function_exists('is_wp_version_compatible') && is_wp_version_compatible('5.3-RC4-46673')) {
                add_filter('intermediate_image_sizes_advanced', array('wpCloud\StatelessMedia\Utility', 'store_can_delete_attachment'), 10, 3);
              }
            }

            if ($this->get('sm.delete_remote') == 'true') {
              /**
               * On physical file deletion we remove any from GS
               * We need priority grater than default (10) for ShortPixel plugin to work properly.
               */
              add_filter('delete_attachment', array($this, 'remove_media'), 11);
            }

            /**
             * init client's filters
             */
            $this->_init_filters('client');
          }
        } elseif ($sm_mode == 'stateless') {
          /**
           * Replacing local path to gs:// for using it on StreamWrapper
           */
          add_filter('upload_dir', array($this, 'filter_upload_dir'), 99);

          /**
           * Stateless mode working only with GD library
           */
          add_filter('wp_image_editors', array($this, 'select_wp_image_editors'));

          //init GS client
          global $gs_client;
          if ($gs_client = $this->init_gs_client()) {
            StreamWrapper::register($gs_client);
          }

          /**
           * init client's filters
           */
          $this->_init_filters('client');

          /**
           * init main filters
           */
          $this->_init_filters('main');
        }
      }

      /**
       * Init additional filters which uses on all modes
       * @param string $type
       */
      private function _init_filters($type = '') {
        switch ($type) {
          case 'main':
            add_filter('wp_get_attachment_image_attributes', array($this, 'wp_get_attachment_image_attributes'), 20, 3);
            add_filter('wp_get_attachment_url', array($this, 'wp_get_attachment_url'), 20, 2);
            add_filter('get_attached_file', array($this, 'get_attached_file'), 9, 2);
            add_filter('attachment_url_to_postid', array($this, 'attachment_url_to_postid'), 20, 2);

            if ($this->get('sm.body_rewrite') == 'true' || $this->get('sm.body_rewrite') == 'enable_editor') {
              add_filter('the_content', array($this, 'the_content_filter'), 99);
            }

            if ($this->get('sm.body_rewrite') == 'true' || $this->get('sm.body_rewrite') == 'enable_meta') {
              add_filter('get_post_metadata', array($this, 'post_metadata_filter'), 2, 4);
            }

            add_filter('wp_stateless_bucket_link', array($this, 'wp_stateless_bucket_link'));
            break;
          case 'client':
            /**
             * Add custom actions to media rows
             */
            add_filter('media_row_actions', array($this, 'add_custom_row_actions'), 10, 3);

            /**
             * Hashify file name if option is enabled
             */
            if ($this->get('sm.hashify_file_name') == 'true') {
              add_filter('sanitize_file_name', array('wpCloud\StatelessMedia\Utility', 'randomize_filename'), 10);
            }

            /**
             * Override Cache Control is option is enabled
             */
            $cacheControl = trim($this->get('sm.cache_control'));
            if (!empty($cacheControl)) {
              add_filter('sm:item:cacheControl', array($this, 'override_cache_control'));
            }

            add_filter('wp_stateless_file_name', array($this, 'handle_root_dir'), 10, 4);

            /**
             * Extends metadata by adding GS information.
             */
            add_filter('wp_get_attachment_metadata', array($this, 'wp_get_attachment_metadata'), 10, 2);

            /**
             * Add/Edit Media
             *
             * Once added or edited we can get into Attachment ID then get all image sizes and sync them with GS
             * We can't use this. That's prevent removing this filter.
             */
            add_filter('wp_update_attachment_metadata', array('wpCloud\StatelessMedia\Utility', 'add_media'), 999, 2);

            /**
             * Rewrite Image URLS
             */
            add_filter('image_downsize', array($this, 'image_downsize'), 99, 3);
            add_filter('wp_calculate_image_srcset', array($this, 'wp_calculate_image_srcset'), 10, 5);

            /**
             * Trigger module initialization and registration.
             */
            do_action('sm::module::init', $this->get('sm'));

            break;
          case 'default':
            break;
        }
      }

      /**
       * The default $editors value:
       *
       * array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' )
       * @param $editors
       * @return array
       */
      public function select_wp_image_editors($editors) {
        return array('WP_Image_Editor_GD');
      }

      /**
       * @param callable|null $httpHandler
       * @return StorageClient
       * @throws \Exception
       */
      public function init_gs_client(callable $httpHandler = null) {
        // May be Loading Google SDK....
        if (!class_exists('HttpHandlerFactory')) {
          include_once(ud_get_stateless_media()->path('lib/Google/vendor/autoload.php', 'dir'));
        }

        $httpHandler = $httpHandler ? $httpHandler : HttpHandlerFactory::build();

        $json_key = json_decode($this->settings->get('sm.key_json'), true);

        if (!empty($json_key)) {
          return new StorageClient(
            [
              'keyFile' => $json_key,
              'httpHandler' => function ($request, $options) use ($httpHandler) {
                $xGoogApiClientHeader = $request->getHeaderLine('x-goog-api-client');
                $request = $request->withHeader('x-goog-api-client', $xGoogApiClientHeader);

                return call_user_func_array($httpHandler, [$request, $options]);
              },
              'authHttpHandler' => HttpHandlerFactory::build(),
            ]
          );
        }
      }

      /**
       * Replacing root dir with GCS path
       * @param $uploads
       * @return array
       */
      public function filter_upload_dir($uploads) {
        global $default_dir;
        if ($default_dir) return $uploads;
        //Bucket
        $bucket = $this->get('sm.bucket');

        //Bucket folder path
        $root_dir = $this->get('sm.root_dir');
        $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);

        /**
         * Subdir not uses on Stateless mode
         */
        $uploads['subdir'] = '';

        $basedir = rtrim(sprintf('gs://%s/%s', $bucket, $root_dir), '/');
        $baseurl = rtrim(sprintf('https://storage.googleapis.com/%s/%s', $bucket, $root_dir), '/');

        $uploads = array(
          'url' => rtrim($baseurl . $uploads['subdir'], '/'),
          'path' => $basedir . $uploads['subdir'],
          'subdir' => $uploads['subdir'],
          'basedir' => $basedir,
          'baseurl' => $baseurl,
          'error' => false,
        );
        return $uploads;
      }

      /**
       * Rebuild srcset from gs_link.
       * Using calculations returned from WordPress wp_calculate_image_srcset()
       *
       * @param $sources
       * @param $size_array
       * @param $image_src
       * @param $image_meta
       * @param $attachment_id
       * @return array
       */
      public function wp_calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        $sm_mode = $this->get('sm.mode');

        /**
         * In Backup mode using local URL
         */
        if ("backup" == $sm_mode) {
          return $sources;
        }

        if (empty($image_meta['gs_link'])) {
          $image_meta = wp_get_attachment_metadata($attachment_id);
        }

        if (is_array($sources) && !empty($image_meta['gs_link'])) {
          $gs_name = $image_meta['gs_name'];
          // getting position of root_dir in gs_name.
          $root_dir_pos = strpos($gs_name, $image_meta['file']);
          // removing rood_dir from gs_name so we can compare to replace url with gs_link.
          if ($root_dir_pos !== false) {
            $gs_name = substr($gs_name, $root_dir_pos);
          }

          if (!isset($gs_name) || empty($gs_name)) {
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
                if ($root_dir_pos !== false) {
                  $thumb_gs_name = substr($thumb_gs_name, $root_dir_pos);
                }

                if (substr_compare($image['url'], $thumb_gs_name, -strlen($thumb_gs_name)) === 0) {
                  $image['url'] = $meta['gs_link'];
                  $found = true;
                  break;
                }
              }

              // if no size found and mode is ephemeral or stateless and nothing to show for srcset item - unset that item
              if (!$found && ($sm_mode === 'ephemeral' || $sm_mode === 'stateless')) {
                $image = null;
              }
            } else {
              // if mode is stateless and nothing to show for srcset item - unset that item
              if ($sm_mode === 'ephemeral' || $sm_mode === 'stateless') {
                $image = null;
              }
            }
          }
        } elseif (is_array($sources) && $sm_mode === 'stateless') {
          foreach ($sources as $width => &$image) {
            // Set default src
            $image['url'] = $image_src;
          }
        }

        return array_filter($sources);
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
        $sm = $sm ? $sm : $this->get('sm');
        $image_host = 'https://storage.googleapis.com/';
        $image_host .=  $sm['bucket'];

        $custom_domain = $sm['custom_domain'];
        $is_ssl = strpos($custom_domain, 'https://');
        $custom_domain = str_replace(array('http://', 'https://'), '', $custom_domain);
        $custom_domain = trim($custom_domain, '/');

        // checking whether the provided domain is valid.
        // if the custom domain is same as the bucket name
        // or the custom domain is using https.
        if (!empty($sm['bucket']) && !empty($custom_domain) && $custom_domain !== 'storage.googleapis.com' && ($is_ssl === 0 || $custom_domain == $sm['bucket'])) {
          $image_host = $is_ssl === 0 ? 'https://' : 'http://';  // bucketname will be host
          $image_host .=  $custom_domain;
        }

        return apply_filters('get_gs_host', $image_host, $image_host, $sm['bucket'], $is_ssl, $sm);
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
        $bucketname = $this->get('sm.bucket');
        $custom_domain = $this->get('sm.custom_domain');
        $is_ssl = strpos($custom_domain, 'https://') === 0;
        $fileLink_is_ssl = strpos($fileLink, 'https://') === 0;
        $custom_domain = str_replace(array('http://', 'https://'), '', $custom_domain);
        $custom_domain = trim($custom_domain, '/');

        if (!empty($bucketname) && $custom_domain !== 'storage.googleapis.com' && $custom_domain == $bucketname && strpos($fileLink, $bucketname) > 8) {
          $fileLink = ($is_ssl ? 'https://' : 'http://') . substr($fileLink, strpos($fileLink, $bucketname));
        } elseif ($custom_domain !== 'storage.googleapis.com' && $custom_domain == $bucketname && $fileLink_is_ssl !== $is_ssl) {
          if ($is_ssl)
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
      public function get_settings_page_url($path = '') {
        $url = get_admin_url(get_current_blog_id(), (is_network_admin() ? 'network/settings.php' : 'upload.php'));
        return $url . $path;
      }

      /**
       * Get new blog settings once switched blog.
       * @param $new_blog
       * @param $prev_blog_id
       */
      public function on_switch_blog($new_blog, $prev_blog_id) {
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
          if (isset($settings[$setting])) {
            continue;
          }

          $value = $this->get('sm.' . $setting);

          /** Decode json to array */
          if ($value && is_string($value) && $setting === 'key_json') {
            $value = json_decode($value, true);
            $setting = 'key';
          }

          $settings[$setting] = $value;
        }

        return $settings;
      }

      /**
       * Remove all settings.
       * @param bool $network
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
      public function add_custom_row_actions($actions, $post, $detached) {

        if (!current_user_can('upload_files')) return $actions;

        $sm_cloud = get_post_meta($post->ID, 'sm_cloud', 1);
        $sm_mode = $this->get('sm.mode');
        if (!empty($sm_cloud) && $sm_mode === 'stateless') return $actions;

        if ($post && 'attachment' == $post->post_type && 'image/' == substr($post->post_mime_type, 0, 6)) {
          $actions['sm_sync'] = '<a href="javascript:;" data-id="' . $post->ID . '" data-type="image" class="sm_inline_sync">' . __('Regenerate and Sync with GCS', ud_get_stateless_media()->domain) . '</a>';
        }

        if ($post && 'attachment' == $post->post_type && 'image/' != substr($post->post_mime_type, 0, 6)) {
          $actions['sm_sync'] = '<a href="javascript:;" data-id="' . $post->ID . '" data-type="other" class="sm_inline_sync">' . __('Sync with GCS', ud_get_stateless_media()->domain) . '</a>';
        }

        return $actions;
      }

      /**
       * Define REST API.
       *
       * @author korotkov@UD
       */
      public function api_init() {

        $route_namespace = 'wp-stateless/v1';
        $api_namespace = 'wpCloud\StatelessMedia\API';

        register_rest_route($route_namespace, '/status', array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($api_namespace, 'status'),
          'permission_callback' => '__return_true'
        ));

        register_rest_route($route_namespace, '/getSettings', array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($api_namespace, 'getSettings'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));

        register_rest_route($route_namespace, '/updateSettings', array(
          'methods' => \WP_REST_Server::CREATABLE,
          'callback' => array($api_namespace, 'updateSettings'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));

        register_rest_route($route_namespace, '/sync/getProcesses', array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($api_namespace, 'syncGetProcesses'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));

        register_rest_route($route_namespace, '/sync/getProcess/(?P<id>\S+)', array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($api_namespace, 'syncGetProcess'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));

        register_rest_route($route_namespace, '/sync/run', array(
          'methods' => \WP_REST_Server::CREATABLE,
          'callback' => array($api_namespace, 'syncRun'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));

        register_rest_route($route_namespace, '/sync/stop', array(
          'methods' => \WP_REST_Server::CREATABLE,
          'callback' => array($api_namespace, 'syncStop'),
          'permission_callback' => array($api_namespace, 'authCheck')
        ));
      }

      /**
       * Metabox for media modal page
       * @param $form_fields
       * @param $post
       * @return array
       */
      public function attachment_modal_meta_box_callback($form_fields, $post) {

        //do not show on media edit page, only on modal
        if (isset($_GET['post'])) {
          return $form_fields;
        }

        $link = get_edit_post_link($post->ID);

        $form_field['label'] = '';
        $form_field['input'] = 'html';
        $form_field['html'] = sprintf("<script>jQuery('.actions').prepend('<a href=\"%s#sm-attachment-metabox\">%s</a> | ')</script>", $link, __("View stateless meta", ud_get_stateless_media()->domain));
        $form_field['show_in_modal'] = true;

        $form_fields['sm_html'] = $form_field;
        return $form_fields;
      }

      /**
       * Metabox for media edit page
       * @param $meta_boxes
       * @return array
       */
      public function attachment_meta_box_callback($meta_boxes) {
        $post_id = false;
        if (isset($_GET['post'])) {
          $post_id = intval($_GET['post']);
        } elseif (isset($_POST['post_ID'])) {
          $post_id = intval($_POST['post_ID']);
        } elseif (isset($_GET['item'])) {
          $post_id = intval($_GET['item']);
        } elseif (isset($_POST['item'])) {
          $post_id = intval($_POST['item']);
        }

        return $this->_prepare_data_for_metabox($meta_boxes, $post_id);
      }

      /**
       * Prepare data for metabox fields
       * @param $meta_boxes
       * @param $post_id
       * @return array
       */
      private function _prepare_data_for_metabox($meta_boxes, $post_id) {
        $post     = get_post($post_id);
        $sm_cloud = get_post_meta($post_id, 'sm_cloud', 1);
        $sm_mode  = $this->get('sm.mode');

        if (empty($post)) {
          return $meta_boxes;
        }

        $sizes = $this->get_image_sizes();

        $fields = array();

        if ($sm_mode !== 'stateless' || empty($sm_cloud)) {
          $fields[] = array(
            'name' =>  __('Regenerate', ud_get_stateless_media()->domain),
            'id'   => 'storage_bucket_url',
            'type' => 'custom_html',
            'std'  => $this->_prepare_generate_link($post, false, '', true, $sm_cloud),
            'tab'  => 'thumbnails',
          );
        }

        if (is_array($sm_cloud) && !empty($sm_cloud['fileLink'])) {

          $fields[] = array(
            'type' => 'heading',
            'name' => 'Files',
            'tab'  => 'thumbnails',
          );

          $fields[] = array(
            'name' =>  __('Original', ud_get_stateless_media()->domain),
            'id'   => 'storage_bucket_url',
            'type' => 'custom_html',
            'media_modal' => true,
            'std'  => '<label><input type="text" class="widefat urlfield" readonly="readonly" value="' . esc_attr($sm_cloud['fileLink']) . '" />
                        <a href="' . $sm_cloud['fileLink'] . '" target="_blank" class="sm-view-link"><i class="dashicons dashicons-external"></i></a>&nbsp;&nbsp;&nbsp;' . $this->_prepare_generate_link($post, true, '', false, $sm_cloud) . ' </label>',
            'tab'  => 'thumbnails',
          );

          if (!empty($sm_cloud['sizes']) && is_array($sm_cloud['sizes'])) {
            foreach ($sm_cloud['sizes'] as $size_label => $size) {

              $fields[] = array(
                'name' =>  __(sprintf("%s x %s", ($size['width'] ?: $sizes[$size_label]['width']), ($size['height'] ?: $sizes[$size_label]['height'])), ud_get_stateless_media()->domain),
                'id'   => 'storage_bucket_url' . $size_label,
                'type' => 'custom_html',
                'media_modal' => true,
                'std'  => '<label><input type="text" class="widefat urlfield" readonly="readonly" value="' . esc_attr($size['fileLink']) . '" />
                            <a href="' . $size['fileLink'] . '" target="_blank" class="sm-view-link"><i class="dashicons dashicons-external"></i></a>&nbsp;&nbsp;&nbsp;' . $this->_prepare_generate_link($post, true, $size_label, false, $sm_cloud) . ' </label>',
                'tab'  => 'thumbnails',
              );
            }
          }

          if (!empty($sm_cloud['cacheControl'])) {
            $fields[] = array(
              'name' =>  __('Cache Control', ud_get_stateless_media()->domain),
              'id'   => 'cache_control',
              'type' => 'custom_html',
              'std'  => '<label><input type="text" class="widefat urlfield" readonly="readonly" value="' . $sm_cloud['cacheControl'] . '" /></label>',
              'tab'  => 'meta',
            );
          }

          if (!empty($sm_cloud['bucket'])) {
            $fields[] = array(
              'name' =>  __('Storage Bucket', ud_get_stateless_media()->domain),
              'id'   => 'storage_bukcet',
              'type' => 'custom_html',
              'std'  => '<label><input type="text" class="widefat urlfield" readonly="readonly" value="gs://' . esc_attr($sm_cloud['bucket']) . '" />
                            <a href="https://console.cloud.google.com/storage/browser/' . esc_attr($sm_cloud['bucket']) . '" target="_blank" class="sm-view-link"><i class="dashicons dashicons-external"></i></a></label>',
              'tab'  => 'meta',
            );
          }
        }

        $meta_boxes[] = apply_filters('sm::attachment::meta', array(
          'id'         => 'sm-attachment-metabox',
          'title'      => __('Stateless', ud_get_stateless_media()->domain),
          'post_types' => 'attachment',
          //'media_modal' => true,
          //set context `side` for left column
          'context'    => 'normal',
          'priority'   => 'low',
          'tabs'      => array(
            'thumbnails' => array(
              'label' => __('Thumbnails', ud_get_stateless_media()->domain),
              'icon'  => 'dashicons-format-gallery',
            ),
            'meta'  => array(
              'label' => __('Meta', ud_get_stateless_media()->domain),
              'icon'  => 'dashicons-admin-site',
            ),
          ),
          // Tab style: 'default', 'box' or 'left'. Optional
          'tab_style' => 'left',
          // Show meta box wrapper around tabs? true (default) or false. Optional
          'tab_wrapper' => true,
          'fields' => $fields
        ), $post->ID);

        return $meta_boxes;
      }

      /**
       * Preparing link for sync
       * @param $post
       * @param bool $use_icon
       * @param string $size
       * @param bool $button
       * @param array $sm_cloud
       * @return string
       */
      private function _prepare_generate_link($post, $use_icon = false, $size = '', $button = false, $sm_cloud = array()) {
        $sync = '';

        $sm_mode = $this->get('sm.mode');

        if (current_user_can('upload_files') && $sm_mode !== 'disabled' && ($sm_mode !== 'stateless' || empty($sm_cloud))) {
          if ($post && 'attachment' == $post->post_type && 'image/' == substr($post->post_mime_type, 0, 6)) {
            $sync = '<a href="javascript:;" data-type="image" data-id="' . $post->ID . '" data-size="' . $size . '" data-reload_page="' . $button . '"
                   class="sm_inline_sync ' . ($button ? 'button button-primary button-large' : '') . '">' . ($use_icon ? "<i class='dashicons dashicons-image-rotate'></i>" : __('Regenerate and Sync with GCS', ud_get_stateless_media()->domain)) . '</a>';
          }
          if ($post && 'attachment' == $post->post_type && 'image/' != substr($post->post_mime_type, 0, 6)) {
            $sync = '<a href="javascript:;" data-type="other" data-id="' . $post->ID . '" data-size="' . $size . '" data-reload_page="' . $button . '"
                   class="sm_inline_sync ' . ($button ? 'button button-primary button-large' : '') . '">' . ($use_icon ? "<i class='dashicons dashicons-image-rotate'></i>" : __('Sync with GCS', ud_get_stateless_media()->domain)) . '</a>';
          }
        } elseif ($button && $sm_mode !== 'stateless') {
          $sync = __('You do not have access to sync or Stateless mode is Disabled', ud_get_stateless_media()->domain);
        }
        return $sync;
      }

      /**
       * @param $current_path
       * @param $use_root boolean: whether to use the root dir or not.
       *        0 will be passed from various compatibilities so that the root dir is not used.
       *        false will passed from some compatibilities to use the value as local path.
       * @param $attachment_id
       * @param $size
       * @return string
       */
      public function handle_root_dir($current_path, $use_root = true, $attachment_id = '', $size = '') {
        global $wpdb;

        //non media files
        if ($use_root === 0) {
          $table_name = $wpdb->prefix . 'sm_sync';
          $non_media = $wpdb->get_var($wpdb->prepare("SELECT file FROM {$table_name} WHERE file like '%%%s';", $current_path));
          if ($non_media) {
            return $non_media;
          }
        }

        $root_dir = $this->get('sm.root_dir');
        $root_dir_regex = '~^' . apply_filters("wp_stateless_handle_root_dir", $root_dir, true) . '/~';
        /**
         * Retrieve Y/M and other tags from current path
         */
        $path_elements = apply_filters('wp_stateless_unhandle_root_dir', $current_path);
        $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir, false, $path_elements);

        $upload_dir = wp_upload_dir();
        $current_path = str_replace(wp_normalize_path(trailingslashit($upload_dir['basedir'])), '', wp_normalize_path($current_path));
        $current_path = str_replace(wp_normalize_path(trailingslashit($upload_dir['baseurl'])), '', wp_normalize_path($current_path));
        $current_path = str_replace(trailingslashit($this->get_gs_host()), '', $current_path);

        /**
         * Using only filename. Other parts of path included to $root_dir.
         * excluding compatibility.
         */
        if ($use_root) {
          $current_path = basename($current_path);
        }

        if (!$use_root) {
          // removing the root dir if already exists in the beginning.
          $raw_name = preg_replace($root_dir_regex, '', $current_path);

          if ($raw_name && is_multisite() && ($blog_id = get_current_blog_id()) != 1) {
            $folder = "sites/{$blog_id}/";
            if (strpos($raw_name, $folder) === 0) return $raw_name;

            return "$folder$raw_name";
          }

          return $raw_name;
        }

        // skip adding root dir if it's already added.
        if (!empty($root_dir) && !preg_match($root_dir_regex, $current_path)) {
          return $root_dir . '/' . trim($current_path, '/ ');
        }

        return $current_path;
      }

      /**
       * @param $content
       * @return mixed
       */
      public function the_content_filter($content) {

        if ($upload_data = wp_upload_dir()) {

          if (!empty($upload_data['url']) && !empty($content)) {
            $url = preg_replace('/https?:\/\//', '', $upload_data['url']);

            $root_dir = trim($this->get('sm.root_dir'), '/ '); // Remove any forward slash and empty space.
            $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);
            $root_dir = !empty($root_dir) ? $root_dir . '/' : false;
            $image_host = $this->get_gs_host();
            $file_ext = $this->replaceable_file_types();
            $content = preg_replace(
              '/(href|src)=(\'|")(https?:\/\/' . str_replace('/', '\/', $url) . ')\/(.+?)(' . $file_ext . ')(\'|")/i',
              '$1=$2' . $image_host . '/' . ($root_dir ? $root_dir : '') . '$4$5$6',
              $content
            );
          }
        }

        return $content;
      }

      /**
       * Return file types supported by File URL Replacement.
       * @return string
       */
      public function replaceable_file_types() {
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
      public function post_metadata_filter($value, $object_id, $meta_key, $single) {
        if (empty($value)) {
          $meta_type = 'post';
          $transient_key = "stateless_{$meta_type}_meta";

          $meta_cache = wp_cache_get($object_id, $transient_key);
          if (empty($meta_cache)) {
            $meta_cache = wp_cache_get($object_id, $meta_type . '_meta');

            if (!$meta_cache) {
              $meta_cache = update_meta_cache($meta_type, array($object_id));
              $meta_cache = $meta_cache[$object_id];
            }

            foreach ($meta_cache as $key => $meta) {
              $meta_cache[$key] = array_map('maybe_unserialize', $meta_cache[$key]);
            }

            $meta_cache = $this->convert_to_gs_link($meta_cache);
            wp_cache_set($object_id, $meta_cache, $transient_key);
          }

          if (!$meta_key) {
            return $meta_cache;
          }

          if (isset($meta_cache[$meta_key])) {
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
       * @param $return
       * @return mixed or null when not changed.
       */
      public function convert_to_gs_link($meta, $return = false) {
        $updated = $meta;
        if ($meta && $upload_data = wp_upload_dir()) {
          if (!empty($upload_data['url']) && !empty($meta)) {
            $url = preg_replace('/https?:\/\//', '', $upload_data['url']);
            $root_dir = trim($this->get('sm.root_dir'), '/ '); // Remove any forward slash and empty space.
            $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);
            $root_dir = !empty($root_dir) ? $root_dir . '/' : false;
            $image_host = $this->get_gs_host() . '/' . ($root_dir ? $root_dir : '');
            $file_ext = $this->replaceable_file_types();
            $updated = $this->_convert_to_gs_link($meta, $image_host, $url, $file_ext);
          }
        }

        if ($updated == $meta && !$return) {
          return null; // Not changed.
        }
        return $updated;
      }

      /**
       * Replace all image link with gs link
       *
       * @param $meta
       * @param $image_host
       * @param $url
       * @param $file_ext
       * @return array|null|string|string[]
       */
      public function _convert_to_gs_link($meta, $image_host, $url, $file_ext) {
        if (is_array($meta)) {
          foreach ($meta as $key => $value) {
            $meta[$key] = $this->_convert_to_gs_link($value, $image_host, $url, $file_ext);
          }
          return $meta;
        } elseif (is_object($meta) && $meta instanceof \stdClass) {
          foreach (get_object_vars($meta) as $key => $value) {
            $meta->{$key} = $this->_convert_to_gs_link($value, $image_host, $url, $file_ext);
          }
          return $meta;
        } elseif (is_string($meta)) {
          return preg_replace('/(https?:\/\/' . str_replace('/', '\/', $url) . ')\/(.+?)(' . $file_ext . ')/i', $image_host . '$2$3', $meta);
        }

        return $meta;
      }

      /**
       * @param $links
       * @param $file
       * @return mixed
       */
      public function plugin_action_links($links, $file) {

        if ($file == plugin_basename(dirname(__DIR__) . '/wp-stateless-media.php')) {
          $settings_link = '<a href="' . '' . '">' . __('Settings', 'ssd') . '</a>';
          array_unshift($links, $settings_link);
        }

        if ($file == plugin_basename(dirname(__DIR__) . '/wp-stateless.php')) {
          $settings_link = '<a href="' . '' . '">' . __('Settings', 'ssd') . '</a>';
          array_unshift($links, $settings_link);
        }

        return $links;
      }

      /**
       * Determines if plugin is loaded via mu-plugins
       * or Network Enabled.
       *
       * @param bool $is_multisite
       * @return bool
       * @author peshkov@UD
       */
      public function is_network_detected($is_multisite = false) {
        /* Plugin is loaded via mu-plugins. */

        if (strpos(Utility::normalize_path($this->root_path), Utility::normalize_path(WPMU_PLUGIN_DIR)) !== false) {
          return true;
        }

        if (is_multisite()) {
          if ($is_multisite) return true;
          /* Looks through network enabled plugins to see if our one is there. */
          foreach (wp_get_active_network_plugins() as $path) {
            // Trying again using readlink in case it's a symlink file.
            // boot_file is already solved.
            // wp_normalize_path is helpfull in windows.
            if (wp_normalize_path($this->boot_file) == wp_normalize_path($path) || (is_link($path) && $this->boot_file == readlink($path))) {
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
        wp_register_style('wp-stateless', $this->path('static/styles/wp-stateless.css', 'url'), array(), self::$version);

        /**
         * select2 styles
         */
        wp_register_style('wp-stateless-select2', $this->path('static/styles/select2.min.css', 'url'), array(), self::$version);

        /* Attachment or upload page */
        wp_register_script('wp-stateless-uploads-js', $this->path('static/scripts/wp-stateless-uploads.js', 'url'), array('jquery'), self::$version);

        /* Setup wizard styles. */
        wp_register_style('wp-stateless-setup-wizard', $this->path('static/styles/wp-stateless-setup-wizard.css', 'url'), array(), self::$version);

        wp_register_script('wp-stateless-select2', ud_get_stateless_media()->path('static/scripts/select2.min.js', 'url'), array('jquery'), self::$version, true);

        /* Stateless settings page */
        wp_register_script('wp-stateless-settings', ud_get_stateless_media()->path('static/scripts/wp-stateless-settings.js', 'url'), array(), self::$version);
        wp_localize_script('wp-stateless-settings', 'stateless_l10n', $this->get_l10n_data());

        wp_register_style('wp-stateless-settings', $this->path('static/styles/wp-stateless-settings.css', 'url'), array(), self::$version);

        // Sync tab
        if (wp_script_is('jquery-ui-widget', 'registered')) {
          wp_register_script('jquery-ui-progressbar', ud_get_stateless_media()->path('static/scripts/jquery-ui/jquery.ui.progressbar.min.js', 'url'), array('jquery-ui-core', 'jquery-ui-widget'), '1.8.6');
        } else {
          wp_register_script('jquery-ui-progressbar', ud_get_stateless_media()->path('static/scripts/jquery-ui/jquery.ui.progressbar.min.1.7.2.js', 'url'), array('jquery-ui-core'), '1.7.2');
        }
        wp_register_script('wp-stateless-angular', ud_get_stateless_media()->path('static/scripts/angular.min.js', 'url'), array(), '1.8.0', true);
        wp_register_script('wp-stateless-angular-sanitize', ud_get_stateless_media()->path('static/scripts/angular-sanitize.min.js', 'url'), array('wp-stateless-angular'), '1.8.0', true);
        wp_register_script('wp-stateless', ud_get_stateless_media()->path('static/scripts/wp-stateless.js', 'url'), array('jquery-ui-core', 'wp-stateless-settings', 'wp-api-request'), self::$version, true);

        wp_localize_script('wp-stateless', 'stateless_l10n', $this->get_l10n_data());
        wp_localize_script('wp-stateless', 'wp_stateless_configs', array(
          'WP_DEBUG' => defined('WP_DEBUG') ? WP_DEBUG : false,
          'REST_API_TOKEN' => Utility::generate_jwt_token(['user_id' => get_current_user_id()], DAY_IN_SECONDS)
        ));

        $settings = ud_get_stateless_media()->get('sm');
        $settings['wildcards'] = $this->settings->wildcards;
        $settings['network_admin'] = is_network_admin();
        $settings['is_multisite'] = is_multisite();
        if (defined('WP_STATELESS_MEDIA_JSON_KEY') && WP_STATELESS_MEDIA_JSON_KEY) {
          $settings['key_json'] = "Currently configured via a constant.";
        }
        wp_localize_script('wp-stateless', 'wp_stateless_settings', $settings);
        wp_localize_script('wp-stateless', 'wp_stateless_compatibility', Module::get_modules());
        wp_register_style('jquery-ui-regenthumbs', ud_get_stateless_media()->path('static/scripts/jquery-ui/redmond/jquery-ui-1.7.2.custom.css', 'url'), array(), '1.7.2');
      }

      /**
       * Get_l10n_data
       *
       * @param string $value
       * @return mixed
       */
      public function get_l10n_data($value = '') {
        include ud_get_stateless_media()->path('l10n.php', 'dir');
        return $l10n;
      }

      /**
       * Admin Scripts
       *
       * @param $hook
       */
      public function admin_enqueue_scripts($hook) {

        switch ($hook) {

          case 'options-media.php':
            wp_enqueue_style('wp-stateless');
            break;

          case 'upload.php':
            wp_enqueue_style('wp-stateless');
            wp_enqueue_script('wp-stateless-uploads-js');

            break;

          case 'post.php':

            global $post;

            if ($post->post_type == 'attachment') {
              wp_enqueue_style('wp-stateless');
              wp_enqueue_script('wp-stateless-uploads-js');
            }

            break;

          case 'media_page_stateless-setup':
          case 'settings_page_stateless-setup':
            wp_enqueue_style('wp-stateless');
            wp_enqueue_style('wp-stateless-setup-wizard');
            break;
          case 'media_page_stateless-settings':
          case 'settings_page_stateless-settings':
            wp_enqueue_style('wp-stateless');
            wp_enqueue_style('wp-stateless-select2');
            wp_enqueue_script('wp-stateless-settings');
            wp_enqueue_script('wp-stateless-select2');
            wp_enqueue_style('wp-stateless-settings');

            // Sync tab
            wp_enqueue_script('jquery-ui-progressbar');
            wp_enqueue_script('wp-stateless-angular');
            wp_enqueue_script('wp-stateless-angular-sanitize');
            wp_enqueue_script('wp-stateless');
            wp_enqueue_style('jquery-ui-regenthumbs');

            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');

            $data = array(
              'key' => 'stateless-cache-busting',
              'class' => 'notice',
              'title' => sprintf(__("Stateless and Ephemeral modes enables and requires the Cache-Busting option.", ud_get_stateless_media()->domain)),
              'message' => sprintf(__("WordPress looks at local files to prevent files with the same filenames. 
                                          Since Stateless mode bypasses this check, there is a potential for files to be stored with the same file name. We enforce the Cache-Busting option to prevent this. 
                                          Override with the <a href='%s' target='_blank'>%s</a> constant.", ud_get_stateless_media()->domain), "https://wp-stateless.github.io/docs/constants/#wp_stateless_media_cache_busting", "WP_STATELESS_MEDIA_CACHE_BUSTING"),
            );
            echo "<script id='template-stateless-cache-busting' type='text/html'>";
            include ud_get_stateless_media()->path('/static/views/error-notice.php', 'dir');
            echo "</script>";

            break;
          default:
            break;
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
      public function wp_get_attachment_image_attributes($attr, $attachment, $size = null) {

        $sm_cloud = get_post_meta($attachment->ID, 'sm_cloud', true);
        if (is_array($sm_cloud) && !empty($sm_cloud['name'])) {
          $attr['class'] = $attr['class'] . ' wp-stateless-item';
          $attr['data-image-size'] = is_array($size) ? implode('x', $size) : $size;
          $attr['data-stateless-media-bucket'] = isset($sm_cloud['bucket']) ? $sm_cloud['bucket'] : false;
          $attr['data-stateless-media-name'] = $sm_cloud['name'];
        }

        return $attr;
      }

      /**
       * Adds filter link to Media Library table.
       *
       * @param $views
       * @return mixed
       */
      public function views_upload($views) {
        $views['stateless'] = '<a href="#">' . __('Stateless Media') . '</a>';
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
      public function image_downsize($false = false, $id, $size) {

        if ((!isset($this->client) || !$this->client || is_wp_error($this->client)) && $this->get('sm.mode') !== 'stateless') {
          return $false;
        }

        /**
         * Check if enabled
         */
        if (!in_array($this->get('sm.mode'), array('cdn', 'stateless', 'ephemeral'))) {
          return $false;
        }

        /** Start determine remote file */
        $img_url = wp_get_attachment_url($id);
        $meta = wp_get_attachment_metadata($id);
        $width = $height = 0;
        $is_intermediate = false;

        //** try for a new style intermediate size */
        if ($intermediate = image_get_intermediate_size($id, $size)) {
          if (!empty($intermediate['gs_link'])) {
            $img_url = $intermediate['gs_link'];
          } else if (!empty($intermediate['url'])) {
            $img_url = $intermediate['url'];
          } else {
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
        if (!$width && !$height) {
          $sm_cloud = get_post_meta($id, 'sm_cloud', true);
          if (is_string($size) && !empty($sm_cloud['sizes']) && !empty($sm_cloud['sizes'][$size])) {
            global $_wp_additional_image_sizes;

            $img_url = !empty($sm_cloud['sizes'][$size]['fileLink']) ? $sm_cloud['sizes'][$size]['fileLink'] : $img_url;

            if (!empty($_wp_additional_image_sizes[$size])) {
              $width = !empty($_wp_additional_image_sizes[$size]['width']) ? $_wp_additional_image_sizes[$size]['width'] : $width;
              $height = !empty($_wp_additional_image_sizes[$size]['height']) ? $_wp_additional_image_sizes[$size]['height'] : $height;
            }

            $is_intermediate = true;
          }
        }

        if (!$width && !$height && isset($meta['width'], $meta['height'])) {

          //** any other type: use the real image */
          $width = $meta['width'];
          $height = $meta['height'];
        }


        if ($img_url) {

          //** we have the actual image size, but might need to further constrain it if content_width is narrower */
          list($width, $height) = image_constrain_size_for_editor($width, $height, $size);
          $img_url = apply_filters('wp_stateless_bucket_link', $img_url);
          return array($img_url, $width, $height, $is_intermediate);
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
      public function wp_get_attachment_metadata($metadata, $attachment_id) {
        global $default_dir;
        $default_dir = false;
        /* Determine if the media file has GS data at all. */
        $sm_cloud = get_post_meta($attachment_id, 'sm_cloud', true);
        // If metadata not passed the get metadata from post meta.
        if (empty($metadata)) {
          $metadata = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        }

        if (empty($metadata)) {
          $metadata = [];
        }

        if (is_array($metadata) && is_array($sm_cloud) && !empty($sm_cloud['fileLink'])) {
          $metadata['gs_link'] = apply_filters('wp_stateless_bucket_link', $sm_cloud['fileLink']);
          $metadata['gs_name'] = isset($sm_cloud['name']) ? $sm_cloud['name'] : false;
          $metadata['gs_bucket'] = isset($sm_cloud['bucket']) ? $sm_cloud['bucket'] : false;
          if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $k => $v) {
              if (!empty($sm_cloud['sizes'][$k]['name'])) {
                $metadata['sizes'][$k]['gs_name'] = $sm_cloud['sizes'][$k]['name'];
                $metadata['sizes'][$k]['gs_link'] = apply_filters('wp_stateless_bucket_link', $sm_cloud['sizes'][$k]['fileLink']);
              }
            }
          }
        }
        if (is_multisite() && !empty($metadata['file'])) {
          if ($this->get('sm.mode') == 'stateless') {
            $default_dir = true;
            $uploads = wp_get_upload_dir();
            $default_dir = false;

            $file_path_fix = $uploads['basedir'] . "/{$metadata['file']}";
            if (file_exists($file_path_fix)) {
              $metadata['file'] = "{$metadata['file']}";
            }
          } else {
            $uploads = wp_get_upload_dir();
            $blog_id = get_current_blog_id();
            $file_path_fix = $uploads['basedir'] . "/sites/$blog_id/{$metadata['file']}";
            if (file_exists($file_path_fix)) {
              $metadata['file'] = "sites/$blog_id/{$metadata['file']}";
            }
          }
        } elseif (empty($sm_cloud) && $this->get('sm.mode') == 'stateless') {
          $default_dir = true;
          $uploads = wp_get_upload_dir();
          $default_dir = false;

          $file_path_fix = $uploads['basedir'] . "/{$metadata['file']}";
          if (file_exists($file_path_fix)) {
            $metadata['file'] = "{$metadata['file']}";
          }
        }

        return $metadata;
      }

      /**
       *
       * @param $file
       * @param $attachment_id
       * @return string
       */
      public function get_attached_file($file, $attachment_id) {
        global $default_dir;
        $sm_cloud = get_post_meta($attachment_id, 'sm_cloud', 1);
        /* Determine if the media file has GS data at all. */
        if (is_multisite()) {
          $sm_cloud = get_post_meta($attachment_id, 'sm_cloud', true);
          $_file = get_post_meta($attachment_id, '_wp_attached_file', true);
          if ($_file) {
            $blog_id = get_current_blog_id();
            $uploads = wp_get_upload_dir();
            $_file = apply_filters('wp_stateless_file_name', $_file, false);
            $file_path_fix = $uploads['basedir'] . "/sites/$blog_id/$_file";
            if (file_exists($file_path_fix)) {
              $file = $file_path_fix;
            }
          }
        } elseif (empty($sm_cloud) && $this->get('sm.mode') == 'stateless') {
          $_file = get_post_meta($attachment_id, '_wp_attached_file', true);
          if ($_file) {
            $default_dir = true;
            $uploads = wp_get_upload_dir();
            $default_dir = false;
            return $uploads['basedir'] . '/' . $_file;
          }
        }
        return $file;
      }

      /**
       * Returns client object
       * or WP_Error on failure.
       *
       * @author peshkov@UD
       * @return object $this->client. \wpCloud\StatelessMedia\GS_Client or \WP_Error
       */
      public function get_client() {

        if (null === $this->client) {

          $key_json = $this->get('sm.key_json');
          if (empty($key_json)) {
            $key_json = get_site_option('sm_key_json');
          }

          /* Try to initialize GS Client */
          $this->client = GS_Client::get_instance(array(
            'bucket' => $this->get('sm.bucket'),
            'key_json' => $key_json
          ));
        }

        return $this->client;
      }

      /**
       * Determines if we can connect to Google Storage Bucket.
       *
       * @author peshkov@UD
       */
      public function is_connected_to_gs() {

        $trnst = get_transient('sm::is_connected_to_gs');

        if (empty($trnst) || false === $trnst || !isset($trnst['hash']) || $trnst['hash'] != md5(serialize($this->get('sm')))) {
          $trnst = array(
            'success' => 'true',
            'error' => '',
            'hash' => md5(serialize($this->get('sm'))),
          );
          $client = $this->get_client();

          if (is_wp_error($client)) {
            $trnst['success'] = 'false';
            $trnst['error'] = $client->get_error_message();
          } else {
            $connected = $client->is_connected();
            if ($connected !== true) {
              $trnst['success'] = 'false';
              $trnst['error'] = sprintf(__('Could not connect to Google Storage bucket. Please, be sure that bucket with name <b>%s</b> exists.', $this->domain), $this->get('sm.bucket'));

              if (is_callable(array($connected, 'getHandlerContext')) && $handlerContext = $connected->getHandlerContext()) {
                if (!empty($handlerContext['error'])) {
                  $handlerContext['error'];
                  $trnst['error'] = "Could not connect to Google Storage bucket. " . make_clickable($handlerContext['error']);
                }
              }

              if (is_callable(array($connected, 'getErrors')) && $error = $connected->getErrors()) {
                $error = reset($error);
                if ($error['reason'] == 'accessNotConfigured')
                  $trnst['error'] = "Could not connect to Google Storage bucket. " . make_clickable($error['message']);
              }
            }
          }
          set_transient('sm::is_connected_to_gs', $trnst, 4 * HOUR_IN_SECONDS);
        }

        if (isset($trnst['success']) && $trnst['success'] == 'false') {
          return new \WP_Error('error', (!empty($trnst['error']) ? $trnst['error'] : __('There is an Error on connection to Google Storage.', $this->domain)));
        }

        return true;
      }

      /**
       * Flush all plugin transients
       *
       */
      public function flush_transients() {
        delete_transient('sm::is_connected_to_gs');
      }

      /**
       * Plugin Activation
       *
       */
      public function activate() {
        add_action('activated_plugin', array($this, 'redirect_to_splash'), 99);
        $this->run_upgrade_process();
      }

      /**
       * Run Install Process.
       * Triggered on plugins_loaded instead of register_activation_hook action.
       * Works on even manual plugin update.
       *
       * @author alim@UD
       */
      public function run_install_process() {
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
      public function run_upgrade_process() {
        // Creating database on new installation.
        $this->create_db();
        /**
         * Maybe Upgrade current Version
         */
        Upgrader::call($this->args['version']);
      }

      /**
       * Create database on plugin activation.
       * @param boolean $force - whether to create db even if option exists. For debug purpose only.
       */
      public function create_db($force = false) {
        global $wpdb;
        $sm_sync_db_version = get_option('sm_sync_db_version');

        if ($sm_sync_db_version && $force == false) {
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('sm_sync_db_version', $this->args['version']);
      }

      /**
       * Delete table when blog is deleted.
       *
       * @param $old_site
       */
      public function wp_delete_site($old_site) {
        global $wpdb;

        switch_to_blog($old_site->id);
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
      public function redirect_to_splash($plugin = '') {
        // $this->settings = new Settings();

        if (defined('WP_CLI') || $this->settings->get('sm.key_json') || isset($_POST['checked']) && count($_POST['checked']) > 1) {
          return;
        }

        if (
          !$this->settings->get('sm.key_json') &&
          defined('WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT') && WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT == true &&
          defined('WP_STATELESS_MEDIA_HIDE_SETTINGS_PANEL') && WP_STATELESS_MEDIA_HIDE_SETTINGS_PANEL == true
        ) {
          return;
        }

        if (!$this->settings->get('sm.key_json') && defined('WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT') && WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT == true) {
          $url = $this->get_settings_page_url('?page=stateless-settings');
          exit(wp_redirect($url));
        }

        if ($plugin == plugin_basename($this->boot_file)) {
          $url = $this->get_settings_page_url('?page=stateless-setup&step=splash-screen');
          if (json_decode($this->settings->get('sm.key_json'))) {
            $url = $this->get_settings_page_url('?page=stateless-settings');
          }
          exit(wp_redirect($url));
        }
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
      }

      /**
       * Show_notice_stateless_cache_busting
       *
       */
      public function show_notice_stateless_cache_busting() {
        $this->errors->add(array(
          'key' => 'stateless_cache_busting',
          'button' => 'View Settings',
          'button_link' => admin_url('upload.php?page=stateless-settings'),
          'title' => sprintf(__("Stateless mode now requires the Cache-Busting option.", ud_get_stateless_media()->domain)),
          'message' => sprintf(__("WordPress looks at local files to prevent files with the same filenames. 
                                Since Stateless mode bypasses this check, there is a potential for files to be stored with the same file name. We enforce the Cache-Busting option to prevent this. 
                                Override with the <a href='%s' target='_blank'>%s</a> constant.", ud_get_stateless_media()->domain), "https://wp-stateless.github.io/docs/constants/#wp_stateless_media_cache_busting", "WP_STATELESS_MEDIA_CACHE_BUSTING"),
        ), 'notice');
      }

      /**
       * Filter for wp_get_attachment_url();
       * @param string $url
       * @param string $post_id
       * @return mixed|null|string
       */
      public function wp_get_attachment_url($url = '', $post_id = '') {
        global $default_dir;
        $sm_cloud = get_post_meta($post_id, 'sm_cloud', 1);
        if (is_array($sm_cloud) && !empty($sm_cloud['fileLink'])) {
          $_url = parse_url($sm_cloud['fileLink']);
          $url = !isset($_url['scheme']) ? ('https:' . $sm_cloud['fileLink']) : $sm_cloud['fileLink'];
          return apply_filters('wp_stateless_bucket_link', $url);
        } elseif (is_multisite() && empty($sm_cloud)) {
          $_file = get_post_meta($post_id, '_wp_attached_file', true);
          if ($_file) {
            if ($this->get('sm.mode') == 'stateless') {
              $default_dir = true;
              $uploads = wp_get_upload_dir();
              $default_dir = false;
              return $uploads['baseurl'] . '/' . $_file;
            } else {
              $uploads = wp_get_upload_dir();
              $default_dir = false;
              $_file = apply_filters('wp_stateless_file_name', $_file, false);
              $blog_id = get_current_blog_id();
              $file_path_fix = $uploads['basedir'] . "/sites/$blog_id/$_file";

              if (file_exists($file_path_fix)) {
                $url = $uploads['baseurl'] . "/sites/$blog_id/$_file";
              }
            }
          }
        } elseif (empty($sm_cloud) && $this->get('sm.mode') == 'stateless') {
          $_file = get_post_meta($post_id, '_wp_attached_file', true);
          if ($_file) {
            $default_dir = true;
            $uploads = wp_get_upload_dir();
            $default_dir = false;

            return $uploads['baseurl'] . '/' . $_file;
          }
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
      public function attachment_url_to_postid($post_id, $url) {
        global $wpdb;

        if (!$post_id) {
          $post_id = get_transient("stateless_url_to_postid_" . md5($url));

          if (defined('WP_STATELESS_LEGACY_URL_TO_POSTID')) {
            // User can use this constant if they change the Bucket Folder (root_dir) after uploading image.
            // This can be little slow at first run.
            if (empty($post_id)) {
              $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'sm_cloud' AND meta_value LIKE '%s'";
              $post_id = $wpdb->get_var($wpdb->prepare($query, '%' . $url . '%'));

              if ($post_id) {
                set_transient("stateless_url_to_postid_" . md5($url), $post_id);
              }
            }
            return $post_id;
          }

          if (empty($post_id)) {
            $gs_base_url =  $this->get_gs_host();
            $root_dir = $this->get('sm.root_dir');
            $path_elements = apply_filters('wp_stateless_unhandle_root_dir', $url);
            $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir, false, $path_elements);
            $gs_url =  $this->get_gs_host() . '/' . $root_dir;
            $site_url = parse_url($gs_url);
            $image_path = parse_url($url);

            //force the protocols to match if needed
            if (isset($image_path['scheme']) && ($image_path['scheme'] !== $site_url['scheme'])) {
              $url = str_replace($image_path['scheme'], $site_url['scheme'], $url);
            }

            if (0 === strpos($url, $gs_url . '/')) {
              $url = substr($url, strlen($gs_url . '/'));
            } else if (0 === strpos($url, $gs_base_url . '/')) {
              // In case user added Bucket Folder (root_dir) after uploading image.
              $url = substr($url, strlen($gs_base_url . '/'));
            }

            /**
             * If `uploads_use_yearmonth_folders` is set - adding year and month to url
             */
            $organize_media   = get_option('uploads_use_yearmonth_folders');
            $path = '';
            if ($organize_media == '1' && isset($path_elements['%date_year/date_month%'])) {
              $path .= $path_elements['%date_year/date_month%'] . '/';
            }
            $url = $path . $url;

            $sql = $wpdb->prepare(
              "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
              $url
            );
            $post_id = $wpdb->get_var($sql);

            if ($post_id) {
              set_transient("stateless_url_to_postid_" . md5($url), $post_id);
            }
          }
        }

        return $post_id;
      }

      /**
       * Change Upload BaseURL when CDN Used.
       *
       * @param $data
       * @return mixed
       */
      public function upload_dir($data) {
        $data['basedir'] = $this->get_gs_host();
        $data['baseurl'] = $this->get_gs_host();
        $data['url'] = $data['baseurl'] . $data['subdir'];

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
      public function parse_feature_flags() {

        try {

          $_raw = file_get_contents(Utility::normalize_path($this->root_path) . 'composer.json');

          $_parsed = json_decode($_raw);

          // @todo Catch poorly formatted JSON.
          if (!is_object($_parsed)) {
            // throw new Error( "unable to parse."  );
          }

          foreach ((array) $_parsed->extra->featureFlags as $_feature) {

            if (!defined($_feature->constant)) {
              define($_feature->constant, $_feature->enabled);

              if ($_feature->enabled) {
                Utility::log('Feature flag ' . $_feature->name . ', [' . $_feature->constant . '] enabled.');
              } else {
                Utility::log('Feature flag ' . $_feature->name . ', [' . $_feature->constant . '] disabled.');
              }
            }
          }
        } catch (\Exception $e) {
          Utility::log('Unable to parse [composer.json] feature flags. Error: [' . $e->getMessage() . ']');
          // echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        return isset($_parsed) ? $_parsed : null;
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
      public function __call($name, $arguments) {
        if (is_callable(array("wpCloud\\StatelessMedia\\Utility", $name))) {
          return call_user_func_array(array("wpCloud\\StatelessMedia\\Utility", $name), $arguments);
        } else {
          return NULL;
        }
      }

      /**
       * Get all thumbnail sizes
       *
       * @global $_wp_additional_image_sizes
       * @uses   get_intermediate_image_sizes()
       *
       * @param  boolean [$unset_disabled = true]
       * @return array
       */
      function get_image_sizes($unset_disabled = true) {
        $wais = &$GLOBALS['_wp_additional_image_sizes'];

        $sizes = array();

        foreach (get_intermediate_image_sizes() as $_size) {
          if (in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
            $sizes[$_size] = array(
              'width'  => get_option("{$_size}_size_w"),
              'height' => get_option("{$_size}_size_h"),
              'crop'   => (bool) get_option("{$_size}_crop"),
            );
          } elseif (isset($wais[$_size])) {
            $sizes[$_size] = array(
              'width'  => $wais[$_size]['width'],
              'height' => $wais[$_size]['height'],
              'crop'   => $wais[$_size]['crop'],
            );
          }

          // size registered, but has 0 width and height
          if ($unset_disabled && ($sizes[$_size]['width'] == 0) && ($sizes[$_size]['height'] == 0))
            unset($sizes[$_size]);
        }

        return $sizes;
      }
    }
  }
}
