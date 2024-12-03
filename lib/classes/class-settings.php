<?php
/**
 * Settings management and UI
 *
 * @since 0.2.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Settings' ) ) {

    final class Settings extends \UDX\Settings {

      const DEFAULT_CACHE_CONTROL = 'public, max-age=36000, must-revalidate';

      /**
       * @var false|null|string
       */
      public $setup_wizard_ui = null;

      /**
       * @var Array
       */
      public $wildcards = array();

      /**
       * @var false|null|string
       */
      public $stateless_settings = null;

      /**
       * @var string
       */
      private $plugin_file = '';

      /**
       * Instance of
       *  - ud_get_stateless_media
       *  - wpCloud\StatelessMedia\Bootstrap
       * @var false|null|string
       */
      public $bootstrap = null;

      private $settings = array(
        'mode'                   => array('WP_STATELESS_MEDIA_MODE', 'ephemeral'),
        'body_rewrite'           => array('WP_STATELESS_MEDIA_BODY_REWRITE', 'false'),
        'body_rewrite_types'     => array('WP_STATELESS_MEDIA_BODY_REWRITE_TYPES', 'jpg jpeg png gif pdf'),
        'bucket'                 => array('WP_STATELESS_MEDIA_BUCKET', ''),
        'root_dir'               => array('WP_STATELESS_MEDIA_ROOT_DIR', ['/%date_year/date_month%/', '/sites/%site_id%/%date_year/date_month%/']),
        'key_json'               => array('WP_STATELESS_MEDIA_JSON_KEY', ''),
        'cache_control'          => array('WP_STATELESS_MEDIA_CACHE_CONTROL', ''),
        'delete_remote'          => array('WP_STATELESS_MEDIA_DELETE_REMOTE', 'true'),
        'custom_domain'          => array('WP_STATELESS_MEDIA_CUSTOM_DOMAIN', ''),
        'organize_media'         => array('', 'true'),
        'hashify_file_name'      => array(['WP_STATELESS_MEDIA_HASH_FILENAME' => 'WP_STATELESS_MEDIA_CACHE_BUSTING'], 'false'),
        'dynamic_image_support'  => array(['WP_STATELESS_MEDIA_ON_FLY' => 'WP_STATELESS_DYNAMIC_IMAGE_SUPPORT'], 'false'),
        'status_email_type'      => array('', 'true'),
        'status_email_address'   => array('', ''),
        'use_postmeta'           => array('WP_STATELESS_POSTMETA', ['false', '']),
        'use_api_siteurl'        => array('WP_STATELESS_API_SITEURL', ['WP_HOME', '']),
      );

      private $network_only_settings = array(
        'hide_settings_panel'   => array('WP_STATELESS_MEDIA_HIDE_SETTINGS_PANEL', false),
        'hide_setup_assistant'  => array('WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT', false),
      );

      private $strings = array(
        'network' => 'Currently configured via Network Settings.',
        'constant' => 'Currently configured via a constant.',
        'environment' => 'Currently configured via an environment variable.',
      );

      /**
       *
       * Settings constructor.
       * @param null $bootstrap
       */
      public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ? $bootstrap : ud_get_stateless_media();

        // Defile the main plugin file
        $realpath = realpath( __DIR__ . '/../..');
        $this->plugin_file = basename( $realpath ) . '/wp-stateless-media.php';

        /* Add 'Settings' link for SM plugin on plugins page. */
        $_basename = plugin_basename( $this->bootstrap->boot_file );

        parent::__construct( array(
          'store'       => 'options',
          'format'      => 'json',
          'data'        => array(
            'sm' => array()
          )
        ));

        // Setting sm variable
        $this->refresh();

        $this->set('page_url.stateless_setup', $this->bootstrap->get_settings_page_url('?page=stateless-setup'));
        $this->set('page_url.stateless_settings', $this->bootstrap->get_settings_page_url('?page=stateless-settings'));

        /** Register options */
        add_action( 'init', array( $this, 'init' ), 3 );

        // additional links on plugins page
        add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2);
        add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2);
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

        // apply wildcard to root dir.
        add_filter( 'wp_stateless_handle_root_dir', array( $this, 'root_dir_wildcards' ), 10, 3);

        // Parse root dir by wildcards
        add_filter( 'wp_stateless_unhandle_root_dir', array( $this, 'parse_root_dir_wildcards' ), 10, 3);

        // Settings page content
        add_action('wp_stateless_settings_tab_content', array($this, 'settings_tab_content'));
        add_action('wp_stateless_processing_tab_content', array($this, 'processing_tab_content'));

        $site_url = parse_url( site_url() );
        $site_url['path'] = isset($site_url['path']) ? $site_url['path'] : '';
        $this->wildcards = array(
          'sites'         => [
            'sites',
            __("sites", $this->bootstrap->domain),
            __("Sites uses for multisite.", $this->bootstrap->domain),
          ],
          '%site_id%'         => [
            get_current_blog_id(),
            __("site id", $this->bootstrap->domain),
            __("Site ID, for example 1.", $this->bootstrap->domain),
          ],
          '%site_url%'        => [
            trim( $site_url['host'] . $site_url['path'], '/ ' ),
            __("site url", $this->bootstrap->domain),
            __("Site URL, for example example.com/site-1.", $this->bootstrap->domain),
          ],
          '%site_url_host%'   => [
            trim( $site_url['host'], '/ ' ),
            __("host name", $this->bootstrap->domain),
            __("Host name, for example example.com.", $this->bootstrap->domain),
          ],
          '%site_url_path%'   => [
            trim( $site_url['path'], '/ ' ),
            __("site path", $this->bootstrap->domain),
            __("Site path, for example site-1.", $this->bootstrap->domain),
          ],
          '%date_year/date_month%' => [
            date('Y').'/'.date('m'),
            __("year and monthnum", $this->bootstrap->domain),
            __("The year of the post, four digits, for example 2004. Month of the year, for example 05", $this->bootstrap->domain),
            "\d{4}\/\d{2}"
          ]
        );
      }

      /**
       * Init
       */
      public function init(){
        $this->save_media_settings();

        add_action('admin_menu', array( $this, 'admin_menu' ));
        /**
         * Manage specific Network Settings
         */
        if( is_network_admin() ) {
          add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ));
        }

      }

      /**
       * Refresh settings
       */
      public function refresh() {
        $this->set( "sm.readonly", []);
        $google_app_key_file = getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: getenv('GOOGLE_APPLICATION_CREDENTIALS');

        foreach ($this->settings as $option => $array) {
          $value    = '';
          $_option  = 'sm_' . $option;
          $constant = $array[0]; // Constant name
          $default  = is_array($array[1]) ? $array[1] : array($array[1], $array[1]); // Default value

          // Getting settings
          $value = get_option($_option, $default[0]);

          if ($option == 'body_rewrite_types' && empty($value) && !is_multisite()) {
            $value = $default[0];
          }

          if ($option == 'hashify_file_name' && in_array($this->get("sm.mode"), array( 'stateless', 'ephemeral' ) )) {
            $value = true;
          }

          // If constant is set then override by constant
          if(is_array($constant)){
            foreach($constant as $old_const => $new_const){
              if(defined($new_const)){
                $value = constant($new_const);
                $this->set( "sm.readonly.{$option}", "constant" );
                break;
              }
              if(is_string($old_const) && defined($old_const)){
                $value = constant($old_const);
                $this->bootstrap->errors->add( array(
                  'key' => $new_const,
                  'title' => sprintf( __( "%s: Deprecated Notice (%s)", $this->bootstrap->domain ), $this->bootstrap->name, $new_const ),
                  'message' => sprintf(__("<i>%s</i> constant is deprecated, please use <i>%s</i> instead.", $this->bootstrap->domain), $old_const, $new_const),
                ), 'notice' );
                $this->set( "sm.readonly.{$option}", "constant" );
                break;
              }
            }
          }
          elseif(defined($constant)){
            $value = constant($constant);
            $this->set( "sm.readonly.{$option}", "constant" );
          }

          // Getting network settings
          if(is_multisite() && $option != 'organize_media' && !$this->get( "sm.readonly.{$option}")){

            $network = get_site_option( $_option, $default[1] );
            // If network settings available then override by network settings.
            if($network || is_network_admin()){
              $value = $network;
              if(!is_network_admin())
                $this->set( "sm.readonly.{$option}", "network" );
            }

          }

          // Converting to string true false for angular.
          if(is_bool($value)){
            $value = $value === true ? "true" : "false";
          }

          $this->set( "sm.$option", $value);
        }

        // Network only settings, to hide settings page
        foreach ($this->network_only_settings as $option => $array) {
          $value    = '';
          $_option  = 'sm_' . $option;
          $constant = $array[0]; // Constant name
          $default  = $array[1]; // Default value

          // If constant is set then override by constant
          if(is_array($constant)){
            foreach($constant as $old_const => $new_const){
              if(defined($new_const)){
                $value = constant($new_const);
                break;
              }
              if(is_string($old_const) && defined($old_const)){
                $value = constant($old_const);
                trigger_error(__(sprintf("<i>%s</i> constant is deprecated, please use <i>%s</i> instead.", $old_const, $new_const)), E_USER_WARNING);
                break;
              }
            }
          }
          elseif(defined($constant)){
            $value = constant($constant);
          }
          // Getting network settings
          elseif(is_multisite()){
            $value = get_site_option( $_option, $default );
          }

          // Converting to string true false for angular.
          if(is_bool($value)){
            $value = $value === true ? "true" : "false";
          }

          $this->set( "sm.$option", $value);
        }

        /**
         * JSON key file path
         */
        /* Use constant value for JSON key file path, if set. */
        if (defined('WP_STATELESS_MEDIA_KEY_FILE_PATH') || $google_app_key_file !== false) {
          /* Maybe fix the path to p12 file. */
          $key_file_path = (defined('WP_STATELESS_MEDIA_KEY_FILE_PATH')) ? WP_STATELESS_MEDIA_KEY_FILE_PATH : $google_app_key_file;

          if( !empty( $key_file_path ) ) {
            $upload_dir = wp_upload_dir();
            /* Check if file exists */
            switch( true ) {
              /* Determine if default path is correct */
              case (file_exists($key_file_path)):
                /* Path is correct. Do nothing */
                break;
              /* Look using WP root. */
              case (file_exists( ABSPATH . $key_file_path ) ):
                $key_file_path = ABSPATH . $key_file_path;
                break;
              /* Look in wp-content dir */
              case (file_exists( WP_CONTENT_DIR . $key_file_path ) ):
                $key_file_path = WP_CONTENT_DIR . $key_file_path;
                break;
              /* Look in uploads dir */
              case (file_exists( wp_normalize_path( $upload_dir[ 'basedir' ] ) . '/' . $key_file_path ) ):
                $key_file_path = wp_normalize_path( $upload_dir[ 'basedir' ] ) . '/' . $key_file_path;
                break;
              /* Look using Plugin root */
              case (file_exists($this->bootstrap->path( $key_file_path, 'dir') ) ):
                $key_file_path = $this->bootstrap->path( $key_file_path, 'dir' );
                break;

            }
            if(is_readable($key_file_path)) {
              $this->set( 'sm.key_json', file_get_contents($key_file_path) );
              if(defined('WP_STATELESS_MEDIA_KEY_FILE_PATH'))
                $this->set( "sm.readonly.key_json", "constant" );
              else
                $this->set("sm.readonly.key_json", "environment");
            }
          }
        }

        $this->set( 'sm.strings', $this->strings );

        do_action('wp_stateless_settings_refresh', $this);
      }

      /**
       * Remove settings
       * @param bool $network
       */
      public function reset($network = false) {
        foreach ($this->settings as $option => $array) {
          if($option == 'organize_media')
            continue;
          $_option = 'sm_' . $option;

          if($network && current_user_can('manage_network')){
            delete_site_option($_option);
            delete_option($_option);
          }
          else{
            delete_option($_option);
          }
        }

        foreach ($this->network_only_settings as $option => $array) {
          $_option = 'sm_' . $option;
          if($network && current_user_can('manage_network')){
            delete_site_option($_option);
            delete_option($_option);
          }
        }

        $this->set('sm', []);
        $this->refresh();
      }

      /**
       * Replacing wildcards with real values
       * @param $root_dir
       * @param bool $regex
       * @param array $current_values
       * @return mixed|null|string|string[]
       *
       */
      public function root_dir_wildcards( $root_dir, $regex = false, $current_values = [] ) {

        $not_allowed_char = '/[^A-Za-z0-9\/_.\.\-]/';
        $wildcards = apply_filters('wp_stateless_root_dir_wildcard', $this->wildcards);

        if($regex){
          $root_dir = preg_quote($root_dir);
          $not_allowed_char = '/[^A-Za-z0-9\/_.\.\-\\\\{}]/';
        }

        if ( is_array( $wildcards ) && !empty( $wildcards ) ) {
          foreach ($wildcards as $wildcard => $values) {
            if (!empty($wildcard)) {
              $replace = $values[0];
              if($regex){
                $replace = isset($values[3]) ? $values[3] : preg_quote($values[0]);
              }
              if ( isset($current_values[$wildcard]) ) {
                $replace = $current_values[$wildcard];
              }
              $root_dir = str_replace($wildcard, $replace, $root_dir);
            }
          }
        }

        //removing all special chars except slash
        $root_dir = preg_replace($not_allowed_char, '', $root_dir);
        $root_dir = preg_replace('/(\/+)/', '/', $root_dir);
        $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.

        return $root_dir;
      }


      /**
       * Parse path by wildcards and return array ('wildcard' => 'value')
       * The perpose of this filter is to return Y/M or other dynamic fields from the file path.
       * For now only Y/M is dynamic. We will get it via using regex.
       * @param $path
       * @return array
       */
      public function parse_root_dir_wildcards( $path ) {
        $result = [];

        /**
         * removing GS host from path
         */
        $gs_url =  $this->bootstrap->get_gs_host();
        if( 0 === strpos( $path, $gs_url . '/' ) ) {
          $path = substr( $path, strlen( $gs_url . '/' ) );
        }

        /**
         * removing filename and last slash
         */
        $path = untrailingslashit( str_replace(basename($path), '', $path) );
        $wildcards = apply_filters('wp_stateless_root_dir_wildcard', $this->wildcards);

        /**
         * Checking if a wildcard have regex field in it.
         * Then return the matching value using regex.
         */
        foreach ($wildcards as $key => $value) {
          if(isset($value[3])){
            if(preg_match("@" . $value[3] . "@", $path, $matches)){
              $result[$key] = $matches[0];
            }
          }
        }

        return $result;
      }

      /**
       * Add menu options
       */
      public function admin_menu() {
        $key_json = $this->get('sm.key_json');
        if($this->get('sm.hide_setup_assistant') != 'true' && empty($key_json) ){
          $this->setup_wizard_ui = add_media_page( __( 'Stateless Setup', $this->bootstrap->domain ), __( 'Stateless Setup', $this->bootstrap->domain ), 'manage_options', 'stateless-setup', array($this, 'setup_wizard_interface') );
        }

        if($this->get('sm.hide_settings_panel') != 'true'){
          $this->stateless_settings = add_media_page( __( 'Stateless Settings', $this->bootstrap->domain ), __( 'Stateless Settings', $this->bootstrap->domain ), 'manage_options', 'stateless-settings', array($this, 'settings_interface') );
        }
      }

      /**
       * Add menu options
       * @param $slug
       */
      public function network_admin_menu($slug) {
        $this->setup_wizard_ui = add_submenu_page( 'settings.php', __( 'Stateless Setup', $this->bootstrap->domain ), __( 'Stateless Setup', $this->bootstrap->domain ), 'manage_options', 'stateless-setup', array($this, 'setup_wizard_interface') );
        $this->stateless_settings = add_submenu_page( 'settings.php', __( 'Stateless Settings', $this->bootstrap->domain ), __( 'Stateless Settings', $this->bootstrap->domain ), 'manage_options', 'stateless-settings', array($this, 'settings_interface') );
      }

      /**
       * Draw interface
       */
      public function settings_interface() {
        $tab = isset($_GET['tab']) && !empty($_GET['tab']) ? $_GET['tab'] : 'stless_settings_tab';

        include $this->bootstrap->path( '/static/views/settings_interface.php', 'dir' );
      }

      /**
       * Draw interface
       */
      public function regenerate_interface() {
        include $this->bootstrap->path( '/static/views/regenerate_interface.php', 'dir' );
      }

      /**
       * Draw interface
       */
      public function setup_wizard_interface() {
        include ud_get_stateless_media()->path( '/static/views/setup_wizard_interface.php', 'dir' );
      }

      /**
       * Handles saving SM data.
       *
       * @author alim@UD
       */
      public function save_media_settings(){
        if(isset($_POST['action']) && $_POST['action'] == 'stateless_settings' && wp_verify_nonce( $_POST['_smnonce'], 'wp-stateless-settings' )){

          $settings = apply_filters('stateless::settings::save', $_POST['sm']);
          $root_dir_value = false;

          foreach ( $settings as $name => $value ) {
            /**
             * Sanitize POST data
             */
            if (!is_array($value)) {
              $value = sanitize_text_field($value);
            }
            /**
             * root_dir settings
             */
            if ( 'root_dir' == $name && is_array($value) ) {
              //managed in WP-Stateless settings (via Bucket Folder control)
              if ( in_array('%date_year/date_month%', $value)) {
                update_option( 'uploads_use_yearmonth_folders', '1'  );
              } else {
                update_option( 'uploads_use_yearmonth_folders', '0'  );
              }

              /**
               * preparing path from tags
               */
              $value = implode('/', $value);
              $root_dir_value = true;
            }

            $option = 'sm_'. $name;

            if($name == 'organize_media'){
              $option = 'uploads_use_yearmonth_folders';
            }
            elseif($name == 'key_json'){
              $value = stripslashes($value);
            }

            // Be sure to cleanup values before saving
            $value = trim($value);

            if(is_network_admin()){
              update_site_option( $option, $value );
            }
            else{
              update_option( $option, $value );
            }
          }

          if ( !$root_dir_value ) {
            if(is_network_admin()){
              update_site_option( 'sm_root_dir', '' );
            }
            else{
              update_option( 'sm_root_dir', '' );
            }
          }

          $this->bootstrap->flush_transients();
          $this->refresh();
        }
      }

      /**
       * Wrapper for setting value.
       * @param string $key
       * @param bool $value
       * @param bool $bypass_validation
       * @return \UDX\Settings
       */
      public function set( $key = '', $value = false, $bypass_validation = false ) {
        return parent::set( $key, $value, $bypass_validation );
      }

      /**
       * Outputs 'Compatibility' tab content on the settings page.
       * 
       */
      public function settings_tab_content() {
        $wildcards = apply_filters('wp_stateless_root_dir_wildcard', $this->wildcards);
        $wildcard_year_month = '%date_year/date_month%';
        $root_dir = $this->get( 'sm.root_dir' );

        $use_year_month = (strpos($root_dir, $wildcard_year_month) !== false) ?: false;

        /**
         * removing year/month wildcard
         */
        if ($use_year_month) {
          $root_dir = str_replace($wildcard_year_month, '%YM%', $root_dir);
        }

        /**
         * preparing array with wildcards
         */
        $root_dir_values = explode('/', $root_dir);

        /**
         * adding year/month wildcard
         */
        if ($use_year_month) {
          if ( !empty($root_dir_values) ) {
            foreach( $root_dir_values as $k=>$root_dir_value ) {
              if ( $root_dir_value == '%YM%' ) {
                $root_dir_values[$k] = $wildcard_year_month;
              }
            }
          } else {
            $root_dir_values[] = $wildcard_year_month;
          }
        }

        /**
         * first slash
         */
        array_unshift($root_dir_values , '/');

        /**
         * removing empty values
         */
        $root_dir_values = array_filter($root_dir_values);

        foreach ($root_dir_values as $k => $v) {
          $root_dir_values[$k] = trim($v);
        }

        /**
         * merging user's wildcards with default values
         */
        $wildcards = array_keys($wildcards);

        if (!empty($root_dir_values)) {
          $wildcards = array_unique( array_merge($root_dir_values, $wildcards) );
        }

        $sm = (object)$this->get('sm');

        include ud_get_stateless_media()->path('static/views/settings-tab.php', 'dir');
      }
      
      /**
       * Outputs 'Sync' tab content on the settings page.
       * 
       */
      public function processing_tab_content() {
        // Drop non-public properties
        $processes = json_encode(Utility::get_available_sync_classes());
        $processes = json_decode($processes, true);

        $processes = Helper::array_of_objects($processes);

        include ud_get_stateless_media()->path('static/views/processing_interface.php', 'dir');
      }

      /**
       * Getter for settings
       *
       * @param string|bool $key
       * @param mixed $default
       * @return mixed
       */
      public function get( $key = false, $default = false ) {
        $value = parent::get( $key, $default );

        if ( $key === 'sm' && is_array($value) ) {
          foreach ( $value as $key => $val ) {
            $value[$key] = apply_filters("wp_stateless_get_setting_$key", $val, $default);
          }
        } else if ( is_string($key) ) {
          $key = str_replace('sm.', '', $key);
          $value = apply_filters("wp_stateless_get_setting_$key", $value, $default);
        }

        return $value;
      }

      /**
       * Override Cache Control
       * 
       * @param $value
       * @return mixed
       */
      public function override_cache_control($value) {
        if ( !empty($value) && $value !== self::DEFAULT_CACHE_CONTROL ) {
          return $value;
        }
        
        $cache_control = trim($this->get('sm.cache_control'));

        return empty($cache_control) ? self::DEFAULT_CACHE_CONTROL : $cache_control;
      }

      /**
       * Add 'Settings' on Plugins page
       * 
       * @param array $actions
       * @param string $file
       * 
       * @return array
       */
      public function plugin_action_links($actions, $file) {
        if ( $file !== $this->plugin_file ) {
          return $actions;
        }

        $url = ud_get_stateless_media()->get_settings_page_url('?page=stateless-settings');

        $settings = [
          'settings' => sprintf( '<a href="%s">%s</a>', $url, __('Settings', ud_get_stateless_media()->domain) ),
        ];

        $actions['addons'] = sprintf( '<a href="%s" style="color: #f05323" target="_blank">%s</a>', ud_get_stateless_media()->get_docs_page_url('addons'), __('Addons', ud_get_stateless_media()->domain) );

        return $settings + $actions;
      }

      /**
       * Add link to docs on Plugins page
       * 
       * @param $meta
       * @param $file
       * 
       * @return array
       */
      public function plugin_row_meta($meta, $file) {
        if ( $file !== $this->plugin_file ) {
          return $meta;
        }

        $meta['documentation'] = sprintf( '<a href="%s" target="_blank">%s</a>', ud_get_stateless_media()->get_docs_page_url(), __('Documentation', ud_get_stateless_media()->domain) );

        return $meta;
      }

    }

  }

}
