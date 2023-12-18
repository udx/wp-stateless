<?php
/**
 * Bootstrap
 *
 * @namespace UsabilityDynamics
 *
 * This file can be used to bootstrap any of the UD plugins.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Bootstrap_Theme' ) ) {

    /**
     * Bootstrap the plugin in WordPress.
     *
     * @class Bootstrap
     * @author: peshkov@UD
     */
    class Bootstrap_Theme extends Bootstrap {
    
      public static $version = '1.0.3';
      
      public $type = 'theme';
      
      /**
       * Slug of parent theme if exist
       *
       * @public
       * @property schema_path
       * @var array
       */
      public $template = false;
      
      /**
       * If theme is child
       *
       * @public
       * @property is_child
       * @var array
       */
      public $is_child = false;
      
      /**
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        //** Load text domain */
        add_action( 'after_setup_theme', array( $this, 'load_textdomain' ), 1 );
        //** TGM Plugin activation. */
        $this->check_plugins_requirements();
        //** May be initialize Licenses Manager. */
        $this->define_license_manager();
        //** Maybe define license client */
        $this->define_license_client();
        add_action( 'after_setup_theme', array( $this, 'pre_init' ), 100 );
        $this->boot();
      }
      
      /**
	     * Determine if we have errors before plugin initialization!
	     *
       * @since 1.0.6
	     */
      public function pre_init() {
        //** Be sure we do not have errors. Do not initialize plugin if we have them. */
        if( $this->has_errors() ) {
          if( !is_admin() ) {
            //** Show message about error on front end only if user administrator! */
            if( current_user_can( 'manage_options' ) ) {
              _e( "Theme is activated with errors. Please, follow instructions on admin panel to solve the issue!", $this->domain );
            }
            die();
          }
        } else {
          $this->init();  
        }
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
        load_theme_textdomain( $this->domain, get_template_directory() . '/static/languages/' );
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

          //** Get custom ( undefined ) Headers from style.css */
          global $wp_theme_directories;
          $stylesheet = get_stylesheet();
          $theme_root = get_raw_theme_root($stylesheet);
          if (false === $theme_root) {
            $theme_root = WP_CONTENT_DIR . '/themes';
          } elseif (!in_array($theme_root, (array)$wp_theme_directories)) {
            $theme_root = WP_CONTENT_DIR . $theme_root;
          }
          $data = get_file_data( $theme_root . '/' . get_stylesheet() . '/style.css', array(
            'uservoice_url' => 'UserVoice',
            'support_url' => 'Support',
          ) );

          $t = wp_get_theme( get_template() );
          $args = array_merge( (array)$args, $data, array(
            'name' => $t->get( 'Name' ),
            'version' => $t->get( 'Version' ),
            'template' => $t->get( 'Template' ),
            'domain' => $t->get( 'TextDomain' ),
            'is_child' => is_child_theme(),
            'root_path' => trailingslashit( wp_normalize_path( get_template_directory() ) ),
            'root_url' => trailingslashit( wp_normalize_path( get_template_directory_uri() ) ),
            'schema_path' => trailingslashit( wp_normalize_path( get_template_directory() ) ) . 'composer.json',
            'boot_file' => trailingslashit( wp_normalize_path( get_template_directory() ) ) . 'style.css',
          ) );
          //echo "<pre>"; print_r( $args ); echo "</pre>"; die();
          $class::$instance = new $class( $args );
        }
        return $class::$instance;
      }
      
    }
  
  }
  
}
