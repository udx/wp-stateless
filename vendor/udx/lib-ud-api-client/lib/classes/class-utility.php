<?php
/**
 * Screen UI
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Utility' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Utility {
      

      /**
       * Returns current url
       *
       * @param mixed $args GET args which should be added to url
       * @param mixed $except_args GET args which will be removed from URL if they exist
       *
       * @return string
       * @author peshkov@UD
       */
      static public function current_url( $args = array(), $except_args = array() ) {
        $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];

        $args        = wp_parse_args( $args );
        $except_args = wp_parse_args( $except_args );

        if( !empty( $args ) ) {
          foreach( (array) $args as $k => $v ) {
            if( is_string( $v ) ) $url = add_query_arg( $k, $v, $url );
          }
        }

        if( !empty( $except_args ) ) {
          foreach( (array) $except_args as $arg ) {
            if( is_string( $arg ) ) $url = remove_query_arg( $arg, $url );
          }
        }

        return $url;
      }
     
    }
  
  }
  
}