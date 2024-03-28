<?php
/**
 * Utility Class
 *
 * @copyright Copyright (c) 2010 - 2013, Usability Dynamics, Inc.
 *
 * @author team@UD
 * @namespace UDX
 * @module Utility
 */
namespace UDX {

  if( !class_exists( 'UDX\Utility' ) ) {

    /**
     * Utility Library.
     *
     * @submodule Utility
     * @version 0.2.2
     * @class Utility
     */
    class Utility {

      /**
       * Class version.
       *
       * @static
       * @property $version
       * @type string
       */
      public static $version = '0.4.0';

      /**
       * Textdomain String
       *
       * @public
       * @property text_domain
       * @var string
       */
      public static $text_domain = 'lib-utility';

      /**
       * Constructor for initializing class, in static mode as well as dynamic.
       *
       * @todo Should make the transdomain configuraiton.
       *
       * @since 0.1.1
       * @author potanin@UD
       */
      public function __construct() {}

      /**
       * Wrapper for wp_parse_args.
       *
       * @author potanin@UD
       * @since 0.3.0
       * @param $args
       * @param $defaults
       *
       * @return object
       */
      static public function parse_args( $args, $defaults ) {

        return (object) wp_parse_args( $args, $defaults );

      }

      /**
       * Detects Variable Type.
       *
       * Distinguishes between object and array based on associative status.
       *
       * @source http://php.net/manual/en/function.gettype.php
       * @since 1.0.4
       */
      static public function get_type( $var ) {

        if( is_object( $var ) ) return get_class( $var );
        if( is_null( $var ) ) return 'null';
        if( is_string( $var ) ) return 'string';

        if( is_array( $var ) ) {

          if( self::is_associative( $var ) ) {
            return 'object';
          }

          return 'array';

        }

        if( is_int( $var ) ) return 'integer';
        if( is_bool( $var ) ) return 'boolean';
        if( is_float( $var ) ) return 'float';
        if( is_resource( $var ) ) return 'resource';

      }

      /**
       * Test if Array is Associative
       *
       * @param $arr
       * @return bool
       */
      static public function is_associative( $arr ) {

        if( !$arr ) {
          return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1) ? true : false;

      }

      /**
       * Port of jQuery.extend() function.
       *
       * @since 1.0.3
       */
      static public function extend() {

        $arrays = func_get_args();
        $base   = array_shift( $arrays );
        if( !is_array( $base ) ) $base = empty( $base ) ? array() : array( $base );
        foreach( (array) $arrays as $append ) {
          if( !is_array( $append ) ) $append = array( $append );
          foreach( (array) $append as $key => $value ) {
            if( !array_key_exists( $key, $base ) and !is_numeric( $key ) ) {
              $base[ $key ] = $append[ $key ];
              continue;
            }
            if( ( isset( $value ) && @is_array( $value ) ) || ( isset( $base[ $key ] ) && @is_array( $base[ $key ] ) ) ) {
              
              // extend if exists, otherwise create.
              if( isset( $base[ $key ] ) ) {
                $base[ $key ] = self::extend( $base[ $key ], $append[ $key ] );
              } else {
                $base[ $key ] = $append[ $key ];
              }            
              
            } else if( is_numeric( $key ) ) {
              if( !in_array( $value, $base ) ) $base[ ] = $value;
            } else {
              $base[ $key ] = $value;
            }
          }
        }

        return $base;
      }

      /**
       * Localization Functionality.
       *
       * Replaces array's l10n data.
       * Helpful for localization of data which is stored in JSON files ( see /schemas )
       *
       * Usage:
       *
       * add_filter( 'ud::schema::localization', function($locals){
       *    return array_merge( array( 'value_for_translating' => __( 'Blah Blah' ) ), $locals );
       * });
       *
       * $result = self::l10n_localize (array(
       *  'key' => 'l10n.value_for_translating'
       * ) );
       *
       *
       * @param array $data
       * @param array $l10n translated values
       * @return array
       * @author peshkov@UD
       */
      static public function l10n_localize( $data, $l10n = array() ) {

        if ( !is_array( $data ) && !is_object( $data ) ) {
          return $data;
        }

        //** The Localization's list. */
        $l10n = apply_filters( 'ud::schema::localization', $l10n );

        //** Replace l10n entries */
        foreach( $data as $k => $v ) {
          if ( is_array( $v ) ) {
            $data[ $k ] = self::l10n_localize( $v, $l10n );
          } elseif ( is_string( $v ) ) {
            if ( strpos( $v, 'l10n' ) !== false ) {
              preg_match_all( '/l10n\.([^\s]*)/', $v, $matches );
              if ( !empty( $matches[ 1 ] ) ) {
                foreach ( $matches[ 1 ] as $i => $m ) {
                  if ( array_key_exists( $m, $l10n ) ) {
                    $data[ $k ] = str_replace( $matches[ 0 ][ $i ], $l10n[ $m ], $data[ $k ] );
                  }
                }
              }
            }
          }
        }

        return $data;
      }
    }

  }

}
