<?php
/**
 * Settings management and UI
 *
 * @since 0.2.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Settings' ) ) {

    final class Settings extends \UsabilityDynamics\Settings {

      /**
       * @var false|null|string
       */
      public $setup_wizard_ui = null;

      /**
       * @var false|null|string
       */
      public $stateless_settings = null;

      private $settings = array(
          'mode'                   => array('WP_STATELESS_MEDIA_MODE', 'cdn'), 
          'body_rewrite'           => array('WP_STATELESS_MEDIA_BODY_REWTITE', 'true'), 
          'on_fly'                 => array('WP_STATELESS_MEDIA_ON_FLY', 'false'), 
          'bucket'                 => array('WP_STATELESS_MEDIA_BUCKET', ''), 
          'root_dir'               => array('WP_STATELESS_MEDIA_ROOT_DIR', ''), 
          'key_json'               => array('WP_STATELESS_MEDIA_JSON_KEY', ''), 
          'override_cache_control' => array('WP_STATELESS_MEDIA_OVERRIDE_CACHE_CONTROL', 'false'), 
          'cache_control'          => array('WP_STATELESS_MEDIA_CACHE_CONTROL', 'public, max-age=36000, must-revalidate'), 
          'delete_remote'          => array('WP_STATELESS_MEDIA_DELETE_REMOTE', 'true'), 
          'custom_domain'          => array('WP_STATELESS_MEDIA_CUSTOM_DOMAIN', ''), 
          'organize_media'         => array('WP_STATELESS_MEDIA_ORGANIZE_MEDIA', 'true'), 
          'hashify_file_name'      => array('WP_STATELESS_MEDIA_HASH_FILENAME', 'true'), 
        );

      /**
       * Overriden construct
       */
      public function __construct() {

        add_action('admin_menu', array( $this, 'admin_menu' ));

        
        $this->save_media_settings();
        

        /* Add 'Settings' link for SM plugin on plugins page. */
        $_basename = plugin_basename( ud_get_stateless_media()->boot_file );

        parent::__construct( array(
          'store'       => 'options',
          'format'      => 'json',
          'data'        => array(
            'sm' => array()
          )
        ));
        
        // Setting sm variable
        $this->refresh();

        /**
         * Manage specific Network Settings
         */
        if( ud_get_stateless_media()->is_network_detected() ) {
          add_action( 'update_wpmu_options', array( $this, 'save_network_settings' ) );
          add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ));
        }

        $this->set('page_url.stateless_setup', ud_get_stateless_media()->get_settings_page_url('?page=stateless-setup'));
        $this->set('page_url.stateless_settings', ud_get_stateless_media()->get_settings_page_url('?page=stateless-settings'));

        /** Register options */
        add_action( 'init', array( $this, 'init' ), 3 );
      }

      public function init(){

      }

      /**
       * Refresh settings
       */
      public function refresh() {
        $network_mode = false;
        $constant_mode = false;
        $upload_data = wp_upload_dir();

        foreach ($this->settings as $option => $array) {
          $_option = 'sm_' . $option;
          $constant = $array[0]; // Constant name
          $default  = $array[1]; // Default value

          if($option == 'organize_media'){
            $_option = 'uploads_use_yearmonth_folders';
          }

          // Getting settings
          $value = get_option($_option, $default);
          // Getting network settings
          if(is_multisite()){
            $network = get_site_option( $_option );
            // If network settings available then override by network settings.
            if($network){
              $value = $network;
              $network_mode = true;
            }
          }

          // If constant is set then override by constant
          if(defined($constant)){
            $value = constant($constant);
          }

          $this->set( "sm.$option", $value);
        }

        if(is_network_admin()){
          $network_mode = false;
        }
        
        $this->set( 'sm.network_mode', $network_mode );
        update_site_option('sm_network_mode', $network_mode);

        /**
         * JSON key file path
         */

        /* Use constant value for JSON key file path, if set. */
        if( defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ) {
          /* Maybe fix the path to p12 file. */
          $key_file_path = WP_STATELESS_MEDIA_KEY_FILE_PATH;

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
              case (file_exists(ud_get_stateless_media()->path( $key_file_path, 'dir') ) ):
                $key_file_path = ud_get_stateless_media()->path( $key_file_path, 'dir' );
                break;

            }
            if(file_exists($key_file_path)){
              $this->set( 'sm.key_json', file_get_contents($key_file_path) );
              $constant_mode = true;
            }
          }
        }
        elseif (defined('WP_STATELESS_MEDIA_JSON_KEY') && json_decode(WP_STATELESS_MEDIA_JSON_KEY)) {
          $constant_mode = true;
        }

        /* Set default cacheControl in case it is empty */
        $cache_control = trim( $this->get( 'sm.cache_control' ) );
        if ( empty( $cache_control ) ) {
          $this->set( 'sm.cache_control', 'public, max-age=36000, must-revalidate' );
        }
        
        $this->set( 'sm.constant_mode', $constant_mode );
        $this->set( 'sm.readonly', $constant_mode ||  $network_mode );
      }

      /**
       * Remove settings
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
        
        delete_site_option('sm_network_mode' );

        $this->set('sm', []);
        $this->refresh();
      }

      /**
       * Add menu options
       */
      public function admin_menu() {
        if(defined('WP_STATELESS_MEDIA_DASHBOARD_CONFIG') && WP_STATELESS_MEDIA_DASHBOARD_CONFIG == false){
          return;
        }

        $this->setup_wizard_ui = add_media_page( __( 'Stateless Media', ud_get_stateless_media()->domain ), __( 'Stateless Media', ud_get_stateless_media()->domain ), 'manage_options', 'stateless-setup', array($this, 'setup_wizard_interface') );
        $this->stateless_settings = add_media_page( __( 'Stateless Settings', ud_get_stateless_media()->domain ), __( 'Stateless Settings', ud_get_stateless_media()->domain ), 'manage_options', 'stateless-settings', array($this, 'settings_interface') );
      }

      /**
       * Add menu options
       */
      public function network_admin_menu($slug) {
        echo $slug;
        $this->setup_wizard_ui = add_submenu_page( 'settings.php', __( 'Stateless Media', ud_get_stateless_media()->domain ), __( 'Stateless Media', ud_get_stateless_media()->domain ), 'manage_options', 'stateless-setup', array($this, 'setup_wizard_interface') );
        $this->stateless_settings = add_submenu_page( 'settings.php', __( 'Stateless Settings', ud_get_stateless_media()->domain ), __( 'Stateless Settings', ud_get_stateless_media()->domain ), 'manage_options', 'stateless-settings', array($this, 'settings_interface') );
      }

      /**
       * Draw interface
       */
      public function settings_interface() {
        include ud_get_stateless_media()->path( '/static/views/settings_interface.php', 'dir' );
      }

      /**
       * Draw interface
       */
      public function regenerate_interface() {
        include ud_get_stateless_media()->path( '/static/views/regenerate_interface.php', 'dir' );
      }

      /**
       * Draw interface
       */
      public function setup_wizard_interface() {
        $step = !empty($_GET['step'])?$_GET['step']:'';
        switch ($step) {
          case 'google-login':
          case 'setup-project':
          case 'finish':
            include ud_get_stateless_media()->path( '/static/views/setup_wizard_interface.php', 'dir' );
            break;
          
          default:
            include ud_get_stateless_media()->path( '/static/views/stateless_splash_screen.php', 'dir' );
            break;
        }
      }

      /**
       * Handles saving SM data.
       *
       * @author alim@UD
       */
      public function save_media_settings(){
        if(isset($_POST['action']) && $_POST['action'] == 'stateless_settings' && wp_verify_nonce( $_POST['_smnonce'], 'wp-stateless-settings' )){ 

          if(
              (defined('WP_STATELESS_MEDIA_DASHBOARD_CONFIG') && WP_STATELESS_MEDIA_DASHBOARD_CONFIG == false) ||
              (get_site_option('sm_network_mode') && !is_network_admin())
            ){
            wp_die('You are unauthorized to edit!');
            return;
          }

          $settings = apply_filters('stateless::settings::save', $_POST['sm']);
          foreach ( $settings as $name => $value ) {
            $option = 'sm_'. $name;

            if($name == 'organize_media'){
              $option = 'uploads_use_yearmonth_folders';
            }

            if(is_network_admin()){
              update_site_option( $option, $value );
            }
            else{
              update_option( $option, $value );
            }
          }

          if(is_network_admin()){
            update_site_option('sm_network_mode', true );
          }

          ud_get_stateless_media()->flush_transients();
        }
      }

      /**
       * Wrapper for setting value.
       * @param string $key
       * @param bool $value
       * @param bool $bypass_validation
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = '', $value = false, $bypass_validation = false ) {

        //if (  $value !== false ) {
        //  update_option( str_replace( '.', '_', $key ), $value );
        //}

        return parent::set( $key, $value, $bypass_validation );

      }

    }

  }

}
