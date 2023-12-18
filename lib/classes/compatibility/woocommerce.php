<?php
/**
 * Compatibility Plugin Name: WooCommerce
 * Compatibility Plugin URI: https://woocommerce.com/
 *
 * Compatibility Description: Ensures compatibility with WooCommerce.
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\WooCommerce' ) ) {

    class WooCommerce extends Compatibility {
      protected $id = 'woocommerce';
      protected $title = 'WooCommerce';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_WOOCOMMERCE';
      protected $description = 'Ensures compatibility with WooCommerce.';
      protected $plugin_file = [ 'woocommerce/woocommerce.php' ];
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        add_filter( 'stateless_skip_cache_busting', array( $this, 'skip_cache_busting' ), 10, 2 );
      }

      /**
       * skip cache busting for template file name.a
       * @param $return
       * @param $filename
       * @return mixed
       */
      public function skip_cache_busting( $return, $filename ) {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 8 );
        if( strpos( $backtrace[ 7 ][ 'class' ], 'WC_CSV_Exporter' ) !== false ) {
          return $filename;
        }
        return $return;
      }

    }

  }

}
