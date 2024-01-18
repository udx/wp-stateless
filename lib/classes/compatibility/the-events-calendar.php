<?php
/**
 * Compatibility Plugin Name: TheEventsCalendar
 * Compatibility Plugin URI: https://wordpress.org/plugins/the-events-calendar/
 *
 * Compatibility Description: Ensures compatibility with The Events Calendar.
 * Noteably: The Events Calendar does not store media, but it uses a fake file called 'silence'
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\TheEventsCalendar' ) ) {

    class TheEventsCalendar extends Compatibility {
      protected $id = 'theeventscalendar';
      protected $title = 'The Events Calendar';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_THEEVENTSCALENDAR';
      protected $description = 'Ensures compatibility with TheEventsCalendar.';
      protected $plugin_file = [ 'the-events-calendar/the-events-calendar.php' ];
      protected $sm_mode_not_supported = [ ];
      protected $enabled = false;
      protected $is_internal = true;

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        add_filter( 'stateless_skip_cache_busting', array( $this, 'skip_cache_busting' ), 10, 2 );
      }

      /**
       * skip cache busting for template file name.
       * @param $return
       * @param $filename
       * @return mixed
       */
      public function skip_cache_busting( $return, $filename ) {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 8 );
        if( strpos( $backtrace[ 7 ][ 'file' ], '/the-events-calendar/' ) !== false ) {
          return $filename;
        }
        return $return;
      }

    }

  }

}
