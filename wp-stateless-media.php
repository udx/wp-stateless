<?php
/**
 * Plugin Name: WP-Stateless
 * Plugin URI: https://udx.io
 * Description: Upload and serve your WordPress media files from Google Cloud Storage.
 * Author: UDX
 * Version: 3.1.1
 * Text Domain: stateless-media
 * Author URI: https://www.udx.io
 *
 * Copyright 2012 - 2020 UDX ( email: info@udx.io )
 *
 */

if( !function_exists( 'ud_get_stateless_media' ) ) {

  /**
   * Returns Stateless Media Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 0.2.0
   * @param bool $key
   * @param null $default
   * @return
   */
  function ud_get_stateless_media( $key = false, $default = null ) {
    $instance = \wpCloud\StatelessMedia\Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_stateless_media' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 0.2.0
   */
  function ud_check_stateless_media() {
    global $_ud_stateless_media_error;
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'stateless-media' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'stateless-media' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'stateless-media' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\wpCloud\StatelessMedia\Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'stateless-media' ) );
      }

      // Include metabox tabs addon
      require_once(  dirname( __FILE__ ) . '/lib/meta-box-tabs/meta-box-tabs.php' );
    } catch( Exception $e ) {
      $_ud_stateless_media_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_stateless_media_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_stateless_media_message() {
    global $_ud_stateless_media_error;
    if( !empty( $_ud_stateless_media_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'stateless-media' ), 'Stateless Media', $_ud_stateless_media_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_stateless_media_message' );
}

if( ud_check_stateless_media() ) {
  //** Initialize. */
  ud_get_stateless_media();
}
