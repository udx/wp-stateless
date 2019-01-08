<?php
/**
 * Helper.
 *
 */
namespace UsabilityDynamics\WP {

  if( !defined( 'ABSPATH' ) ) {
    die();
  }

  if (!class_exists('UsabilityDynamics\WP\Utility')) {

    class Utility {

      /**
       *  Return root path to library.
       */
      static public function path( $shortpath, $type = 'dir' ) {
        /** Determine where th library installed ( it's a theme or plugin ) */
        if( defined( 'WPMU_PLUGIN_DIR' ) && strpos( wp_normalize_path( dirname(__FILE__) ), wp_normalize_path( WPMU_PLUGIN_DIR ) ) !== false
        ) {
          $instance = 'mu-plugin';
        } elseif ( defined( 'WP_PLUGIN_DIR' ) &&  strpos( wp_normalize_path( dirname(__FILE__) ), wp_normalize_path( WP_PLUGIN_DIR ) ) !== false
        ){
          $instance = 'plugin';
        } elseif ( strpos( wp_normalize_path( get_template_directory() ), wp_normalize_path( dirname(__FILE__) ) ) !== false ){
          $instance = 'template';
        } else {
          $instance = 'stylesheet';
        }

        $path = false;
        switch( $type ) {
          case 'dir':
            $path = self::_path_dir( $instance );
            break;
          case 'url':
            $path = self::_path_url( $instance );
            break;
        }
        if( $path ) {
          $path = preg_replace( '|^(.*)(\/lib)(\/classes)(\/)?$|', '$1', $path );
          $path = trailingslashit( $path );
          $path .= ltrim( $shortpath, '/\\' );
        }
        return $path;
      }

      /**
       * Returns correct absolute URL path to lib-ui directory
       *
       * @param $instance
       * @return string
       */
      static private function _path_url( $instance ) {
        $path = false;
        switch( $instance ) {
          case 'mu-plugin':
          case 'plugin':
            $path = plugin_dir_url( __FILE__ );
            break;
          case 'template':
            $s = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path(get_template_directory()) );
            $s = str_replace( '/', '\/', $s );
            $reg = '|^(.)*(' . $s . ')(.*)$|';
            $p = preg_replace( $reg, '$3', wp_normalize_path( dirname( __FILE__ ) ) );
            $path = get_template_directory_uri() . $p;
            break;
          case 'stylesheet':
            $s = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path(get_stylesheet_directory()) );
            $s = str_replace( '/', '\/', $s );
            $reg = '|^(.)*(' . $s . ')(.*)$|';
            $p = preg_replace( $reg, '$3', wp_normalize_path( dirname( __FILE__ ) ) );
            $path = get_stylesheet_directory_uri() . $p;
            break;
        }
        return $path;
      }

      /**
       * Returns correct absolute DIR path to lib-ui directory
       *
       * @param $instance
       * @return string
       */
      static private function _path_dir( $instance ) {
        $path = false;
        switch( $instance ) {
          case 'mu-plugin':
          case 'plugin':
            $path = plugin_dir_path( __FILE__ );
            break;
          case 'template':
          case 'stylesheet':
            $path = dirname( __FILE__ );
            break;
        }
        if( $path )
          $path = wp_normalize_path( $path );
        return $path;
      }

    }

  }

}