<?php
/**
 * UD API Updater
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Bootstrap' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Bootstrap extends Scaffold {
    
      /**
       *
       */
      public static $version = '1.0.0';
    
      /**
       *
       */
      private $products = array();
      
      /**
       *
       */
      public $admin;
      
      /**
       *
       */
      public function __construct( $args = array() ) {
        global $_ud_license_updater;
        parent::__construct( $args );
        //** Maybe get queued theme update */
        if( $this->type == 'theme' ) {
          $this->maybe_get_queued_theme_update();
        } 
        //** Get queued plugin updates. */
        elseif ( $this->type == 'plugin' ) {
          add_action( 'plugins_loaded', array( $this, 'load_queued_updates' ), 10 );
        }
        $_ud_license_updater = !is_array( $_ud_license_updater ) ? array() : $_ud_license_updater;
        $_ud_license_updater[ $this->slug ] = $this;
        //** Load the admin. */
        if ( is_admin() ) {
          $this->admin = new Admin( $args );          
        }
        
        /**
         * HACK.
         * Filter the whitelist of hosts to redirect to.
         * It adds Admin URL ( in case it's different with home or site urls )
         * to allowed list.
         *
         * @param array       $hosts An array of allowed hosts.
         * @param bool|string $host  The parsed host; empty if not isset.
         */
        add_filter( 'allowed_redirect_hosts', function( $hosts, $host ) {
          if( !is_array( $hosts ) ) {
            $hosts = array();
          }
          $schema = parse_url( admin_url() );
          if( isset( $schema[ 'host' ] ) ) {
            array_push( $hosts, $schema[ 'host' ] );
            $hosts = array_unique( $hosts );
          }
          return $hosts;
        }, 99, 2 );
      }
      
      /**
       * Add a product to await a license key for activation.
       *
       * Add a product into the array, to be processed with the other products.
       *
       * @since  1.0.0
       * @param string $file The base file of the product to be activated.
       * @param string $instance_key The unique ID of the product to be activated.
       * @return  void
       */
      public function add_product ( $file, $instance_key, $product_id, $errors_callback ) {
        if ( $file != '' && !isset( $this->products[ $file ] ) ) { 
          $this->products[ $file ] = array( 'instance_key' => $instance_key, 'product_id' => $product_id, 'errors_callback' => $errors_callback ); 
        }
      }
      
      /**
       * Return an array of the available product keys.
       * @since  1.0.0
       * @return array Product keys.
       */
      public function get_products () {
        return (array) $this->products;
      }
      
      /**
       * Add 'Plugin' Product.
       *
       * @access public
       * @since 1.0.0
       * @return void
       */
      public function load_queued_updates() {
        global $_ud_queued_updates;
        //echo "<pre>"; print_r( $_ud_queued_updates ); echo "</pre>"; die();
        if ( !empty( $_ud_queued_updates[ $this->slug ] ) && is_array( $_ud_queued_updates[ $this->slug ] ) ) {
          foreach ( $_ud_queued_updates[ $this->slug ] as $plugin ) {
            if ( is_object( $plugin ) && ! empty( $plugin->file ) && ! empty( $plugin->instance_key ) && ! empty( $plugin->product_id ) ) {
              $errors_callback = isset( $plugin->errors_callback ) ? $plugin->errors_callback : false;
              $this->add_product( $plugin->file, $plugin->instance_key, $plugin->product_id, $errors_callback );
            }
          }
        }
      }
      
      /**
       * Add 'Theme' Product.
       *
       * @access public
       * @since 1.0.0
       * @return void
       */
      public function maybe_get_queued_theme_update() {
        global $_ud_queued_updates;
        //echo "<pre>"; print_r( $_ud_queued_updates[ '_theme_' ] ); echo "</pre>"; die();
        if ( !empty( $_ud_queued_updates[ '_theme_' ] ) && is_object( $_ud_queued_updates[ '_theme_' ] ) ) {
          $theme = $_ud_queued_updates[ '_theme_' ];
          if( ! empty( $theme->file ) && ! empty( $theme->instance_key ) && ! empty( $theme->product_id ) ) {
            $errors_callback = isset( $theme->errors_callback ) ? $theme->errors_callback : false;
            $this->add_product( $theme->file, $theme->instance_key, $theme->product_id, $errors_callback );
          }
        }
      }
      
    }
  
  }
  
}
