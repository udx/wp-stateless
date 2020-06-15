<?php
/**
 * Theme Name: Divi
 * Theme URI: https://www.elegantthemes.com/gallery/divi/
 *
 * Compatibility Description: Ensures compatibility with Divi themes Builder Export addon.
 * https://github.com/wpCloud/wp-stateless/issues/224
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Divi' ) ) {

    class Divi extends ICompatibility {
      protected $id = 'divi';
      protected $title = 'Divi';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_DIVI';
      protected $description = 'Ensures compatibility with Divi theme.';
      protected $theme_name = 'Divi';

      /**
       * Cache Busting call stack conditions to disable.
       * Fixing the issue with multiple cache files being created on each page load.
       * @see https://github.com/wpCloud/wp-stateless/issues/430
       * @var array
       */
      private $cache_busting_disable_conditions = array(
        array( 'stack_level' => 7, 'function' => '__construct', 'class' => 'ET_Core_PageResource' ),
        array( 'stack_level' => 7, 'function' => 'get_cache_filename', 'class' => 'ET_Builder_Element' )
      );

      /**
       * Initialize compatibility module
       * @param $sm
       */
      public function module_init( $sm ) {
        // exclude randomize_filename from export
        if( !empty( $_GET[ 'et_core_portability' ] ) || wp_doing_ajax() && ( !empty( $_POST[ 'action' ] )
            && $_POST[ 'action' ] == 'et_core_portability_export' ) || ( !empty( $_POST[ 'et_core_portability_export' ] )
            && $_POST[ 'et_core_portability_export' ] == 'et_core_portability_export' ) ) {
          remove_filter( 'sanitize_file_name', array( "wpCloud\StatelessMedia\Utility", 'randomize_filename' ), 10 );
        }

        // maybe skip cache busting
        add_filter( 'stateless_skip_cache_busting', array( $this, 'maybe_skip_cache_busting' ), 10, 2 );
      }

      /**
       * Maybe skip cache busting
       * @param $null
       * @param $filename
       * @return bool | string
       */
      public function maybe_skip_cache_busting( $null, $filename ) {
        $callstack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 8 );
        if( Utility::isCallStackMatches( $callstack, $this->cache_busting_disable_conditions ) ) return $filename;
        return $null;
      }

    }

  }

}
