<?php
/**
 * Scaffold
 *
 * @namespace UsabilityDynamics
 *
 * This file can be used to bootstrap any of the UD plugins, it essentially requires that you have
 * a core file which will be called after 'plugins_loaded'. In addition, if the core class has
 * 'activate' and 'deactivate' functions, then those will be called automatically by this class.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Scaffold' ) ) {

    /**
     *
     * @class Scaffold
     * @author: peshkov@UD
     */
    abstract class Scaffold {
    
      /**
       * Plugin ( Theme ) Name.
       *
       * @public
       * @property $name
       * @type string
       */
      public $name = false;
      
      /**
       * Slug.
       *
       * @public
       * @property $plugin
       * @type string
       */
      public $slug = false;

      /**
       * Textdomain String
       *
       * @public
       * @property domain
       * @var string
       */
      public $domain = false;
      
      /**
       * Root path
       *
       * @public
       * @property root_path
       * @var string
       */
      public $root_path = false;
      
      /**
       * Root URL
       *
       * @public
       * @property root_url
       * @var string
       */
      public $root_url = false;

      /**
       * UserVoice URL
       *
       * @public
       * @property uservoice_url
       * @var string
       */
      public $uservoice_url = false;

      /**
       * Support URL
       *
       * @public
       * @property support_url
       * @var string
       */
      public $support_url = false;
      
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
      protected function __construct( $args = array() ) {
        $_args = array();
        foreach( $args as $k => $v ) {
          if( property_exists( $this, $k ) ) {
            $prop = new \ReflectionProperty( $this, $k );
            if( !$prop->isStatic() ) {
              switch( $k ) {
                case 'root_path':
                  $this->root_path = trailingslashit( trim( $v ) );
                  break;
                case 'name':
                  $this->slug = sanitize_key( trim( $v ) );
                default:
                  $this->{$k} = trim( $v );
                  break;
              }
            } else {
              $_args[ $k ] = $v;
            }
          } else {
            $_args[ $k ] = $v;
          }
        }
        $this->args = $_args;
        
        //** Debug data */
        if( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
          $trace = debug_backtrace();
          $this->debug = array(
            /** Where from the current class is called */
            'backtrace_path' => $trace[0]['file'],
          );
        }
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