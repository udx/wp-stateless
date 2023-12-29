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