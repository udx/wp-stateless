<?php
/**
 * Manager
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Manager' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Manager {
    
      private $type;
      private $product_id;
      private $referrer;
      private $name;
      private $boot_file;
      private $instance_key;
      
      public $errors_callback;
      
      /**
       * Constructor
       */
      public function __construct( $schema = array() ) {
        $this->type = !empty( $schema[ 'type' ] ) ? $schema[ 'type' ] : false;
        $this->product_id = !empty( $schema[ 'product_id' ] ) ? $schema[ 'product_id' ] : false;
        $this->referrer = !empty( $schema[ 'referrer' ] ) ? $schema[ 'referrer' ] : false;
        $this->name = !empty( $schema[ 'name' ] ) ? $schema[ 'name' ] : false;
        $this->boot_file = !empty( $schema[ 'boot_file' ] ) ? $schema[ 'boot_file' ] : false;
        $this->errors_callback = !empty( $schema[ 'errors_callback' ] ) ? $schema[ 'errors_callback' ] : false;
        $this->queue_updates();
      }
      
      /**
       * Add product to global list of products.
       */
      public function queue_updates() {
        global $_ud_queued_updates;

        if( !$this->product_id || !$this->boot_file || !$this->name ) {
          return false;
        }
        
        //** Get instance key. If it does not exist: generate it. */
        $option_key = sanitize_key( $this->name ) . ':instance';
        $this->instance_key = get_option( $option_key, false );
        if( empty( $this->instance_key ) ) {
          $this->instance_key = $this->generate_password( 12, false );
          update_option( $option_key, $this->instance_key );
        }
        
        $product                  = new \stdClass();
        $product->type            = $this->type;
        $product->product_id      = $this->product_id;
        $product->instance_key    = $this->instance_key;
        $product->errors_callback = $this->errors_callback;

        $_ud_queued_updates = isset( $_ud_queued_updates ) ? $_ud_queued_updates : array();
        //** Add theme */
        if( $product->type === 'theme' ) {
          $product->file = $this->boot_file;
          //** Must be only one theme in the list! */
          if( !empty( $_ud_queued_updates[ '_theme_' ] ) ) {
            //** WTF? How it could be? */
            wp_die( 'Are you cheating?' );
          } else {
            $_ud_queued_updates[ '_theme_' ] = $product;
          }
        } 
        //** Add plugin */
        elseif ( $product->type === 'plugin' ) {
          $product->file = plugin_basename( $this->boot_file );
          if( !$this->referrer ) {
            //** WTF? How it could be? */
            wp_die( 'Are you cheating?' );
          }
          $referrer_key = strtolower( $this->referrer );
          $referrer_key = preg_replace( '/[^a-z0-9_\-\/]/', '', $referrer_key );
          $_ud_queued_updates[ $referrer_key ] = isset( $_ud_queued_updates[ $referrer_key ] ) ? $_ud_queued_updates[ $referrer_key ] : array();
          $_ud_queued_updates[ $referrer_key ][] = $product; 
        }
        return true;
      }
      
      /**
       * Creates a unique instance ID
       */
      private function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ( $special_chars ) {
          $chars .= '!@#$%^&*()';
        }
        if ( $extra_special_chars ) {
          $chars .= '-_ []{}<>~`+=,.;:/?|';
        }
        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
          $password .= substr( $chars, $this->rand(0, strlen($chars) - 1), 1);
        }
        return $password;
      }
      
      /**
       * 
       */
      private function rand( $min = 0, $max = 0 ) {
        global $rnd_value;
        //** Reset $rnd_value after 14 uses */
        //** 32(md5) + 40(sha1) + 40(sha1) / 8 = 14 random numbers from $rnd_value */
        if ( strlen($rnd_value) < 8 ) {
          if ( defined( 'WP_SETUP_CONFIG' ) )
            static $seed = '';
          else
            $seed = get_transient('random_seed');
          $rnd_value = md5( uniqid(microtime() . mt_rand(), true ) . $seed );
          $rnd_value .= sha1($rnd_value);
          $rnd_value .= sha1($rnd_value . $seed);
          $seed = md5($seed . $rnd_value);
          if ( ! defined( 'WP_SETUP_CONFIG' ) )
            set_transient('random_seed', $seed);
        }
        //** Take the first 8 digits for our value */
        $value = substr($rnd_value, 0, 8);
        //** Strip the first eight, leaving the remainder for the next call to wp_rand(). */
        $rnd_value = substr($rnd_value, 8);
        $value = abs(hexdec($value));
        //** Some misconfigured 32bit environments (Entropy PHP, for example) truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats. */
        $max_random_number = 3000000000 === 2147483647 ? (float) "4294967295" : 4294967295; // 4294967295 = 0xffffffff
        //** Reduce the value to be within the min - max range */
        if ( $max != 0 ) {
          $value = $min + ( $max - $min + 1 ) * $value / ( $max_random_number + 1 );
        }
        return abs(intval($value));
      }
      
    }
  
  }
  
}