<?php
/**
 * Scaffold
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Scaffold' ) ) {

    /**
     *
     * @class Scaffold
     * @author: peshkov@UD
     */
    abstract class Scaffold {
      
      public $type;
      public $blog;
      public $name;
      public $slug;
      public $referrer_slug;
      public $domain;
      
      /** 
       * Email is required for activation.
       *
       * @var boolean
       */
      public $activation_email;
      
      /**
       * Storage for dynamic properties
       * Used by magic __set, __get
       *
       * @protected
       * @type array
       */
      protected $_properties = array();
      
      /**
       * Constructor
       *
       * @author peshkov@UD
       */
      public function __construct( $args = array() ) {
        //** Setup our plugin's data */
        $this->type = isset( $args[ 'type' ] ) ? trim( $args[ 'type' ] ) : false;
        $this->activation_email = isset( $args[ 'activation_email' ] ) && $args[ 'activation_email' ] ? true : false;
        $this->name = isset( $args[ 'name' ] ) ? trim( $args[ 'name' ] ) : false;
        $this->slug = isset( $args[ 'slug' ] ) ? trim( $args[ 'slug' ] ) : sanitize_key( $this->name );
        $this->referrer_slug = isset( $args[ 'referrer_slug' ] ) ? $args[ 'referrer_slug' ] : false;
        $this->domain = isset( $args[ 'domain' ] ) ? trim( $args[ 'domain' ] ) : false;
        
        /**
         * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
         * so only the host portion of the URL can be sent. For example the host portion might be
         * www.example.com or example.com. http://www.example.com includes the scheme http,
         * and the host www.example.com.
         * Sending only the host also eliminates issues when a client site changes from http to https,
         * but their activation still uses the original scheme.
         * To send only the host, use a line like the one below:
         */
        $this->blog = str_ireplace( array( 'http://', 'https://' ), '', home_url() );
        
        $this->args = $args;
      }
      
      /**
       *
       */
      public function __get( $key ) {
        return isset( $this->_properties[ $key ] ) ? $this->_properties[ $key ] : NULL;
      }

      /**
       *
       */
      public function __set( $key, $value ) {
        $this->_properties[ $key ] = $value;
      }
      
    }
  
  }
  
}