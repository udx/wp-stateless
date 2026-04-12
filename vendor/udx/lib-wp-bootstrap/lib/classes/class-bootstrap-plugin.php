<?php
/**
 * Bootstrap
 *
 * @namespace UsabilityDynamics
 *
 * This file can be used to bootstrap any of the UD plugins.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Bootstrap_Plugin' ) ) {

    /**
     * Bootstrap the plugin in WordPress.
     *
     * @class Bootstrap
     * @author: peshkov@UD
     */
    class Bootstrap_Plugin extends Bootstrap {
    
      public static $version = '1.0.4';
      
      public $type = 'plugin';
      
      /**
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        //** Maybe define license client */
        $this->define_license_client();
        //** Load text domain */
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 1 );
        //** May be initialize Licenses Manager. */
        add_action( 'plugins_loaded', array( $this, 'define_license_manager' ), 1 );
        //** Initialize plugin here. All plugin actions must be added on this step */
        add_action( 'plugins_loaded', array( $this, 'pre_init' ), 100 );
        //** TGM Plugin activation. */
        add_action( 'plugins_loaded', array( $this, 'check_plugins_requirements' ), 10 );
        $this->boot();
      }
      
      /**
	     * Determine if we have errors before plugin initialization!
	     *
       * @since 1.0.3
	     */
      public function pre_init() {
        //** Be sure we do not have errors. Do not initialize plugin if we have them. */
        if( $this->has_errors() ) {
          return null;
        }
        $this->init();
      }

	    /**
	     * Returns absolute DIR or URL path
	     *
	     * @since 1.0.2
	     *
	     * @param $short_path
	     * @param string $type
	     *
	     * @return bool|string
	     */
      public function path( $short_path, $type = 'url' ) {
        switch( $type ) {
          case 'url':
            return $this->root_url . ltrim( $short_path, '/\\' );
            break;
          case 'dir':
            return $this->root_path . ltrim( $short_path, '/\\' );
            break;
        }
        return false;
      }
      
      /**
       * Called in the end of constructor.
       * Redeclare the method in child class!
       *
       * @author peshkov@UD
       */
      public function boot() {}
      
      /**
       * Load Text Domain
       *
       * @author peshkov@UD
       */
      public function load_textdomain() {
        load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( $this->boot_file ) ) . '/static/languages/' );
      }
      
      /**
       * Determine if instance already exists and Return Instance
       *
       * Attention: The method MUST be called from plugin core file at first to set correct path to plugin!
       *
       * @author peshkov@UD
       */
      public static function get_instance( $args = array() ) {
        $class = get_called_class();
        //** We must be sure that final class contains static property $instance to prevent issues. */
        if( !property_exists( $class, 'instance' ) ) {
          exit( "{$class} must have property \$instance" );
        }
        $prop = new \ReflectionProperty( $class, 'instance' );
        if( !$prop->isStatic() ) {
          exit( "Property \$instance must be <b>static</b> for {$class}" );
        }
        if( null === $class::$instance ) {    
          $dbt = debug_backtrace();
          if( !empty( $dbt[0]['file'] ) && file_exists( $dbt[0]['file'] ) ) {
            $pd = get_file_data( $dbt[0]['file'], array(
              'name' => 'Plugin Name',
              'version' => 'Version',
              'domain' => 'Text Domain',
              'uservoice_url' => 'UserVoice',
              'support_url' => 'Support',
            ), 'plugin' );
            $args = array_merge( (array)$pd, (array)$args, array(
              'root_path' => dirname( $dbt[0]['file'] ),
              'root_url' => plugin_dir_url( $dbt[0]['file'] ),
              'schema_path' => dirname( $dbt[0]['file'] ) . '/composer.json',
              'boot_file' => $dbt[0]['file'],
            ) );
            $class::$instance = new $class( $args );
            //** Register activation hook */
            register_activation_hook( $dbt[0]['file'], array( $class::$instance, '_activate' ) );
            //** Register activation hook */
            register_deactivation_hook( $dbt[0]['file'], array( $class::$instance, '_deactivate' ) );
          } else {
            $class::$instance = new $class( $args );
          }
        }
        return $class::$instance;
      }

      /**
       * Plugin Activation
       * Internal method. Use activate() instead
       */
      public function _activate() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        delete_option( 'dismissed_warning_' . sanitize_key( $this->name ) );
        $this->activate();
      }

      /**
       * Plugin Deactivation
       * Internal method. Use deactivate() instead
       */
      public function _deactivate() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        delete_option( 'dismissed_warning_' . sanitize_key( $this->name ) );
        $this->deactivate();
      }

      /**
       * Plugin Activation
       * Redeclare the method in child class!
       */
      public function activate() {}
      
      /**
       * Plugin Deactivation
       * Redeclare the method in child class!
       */
      public function deactivate() {}
      
    }
  
  }
  
}
