<?php
/**
 * Plugin Name: VidoRev
 * Plugin URI: http://demo.beeteam368.com/vidorev/
 *
 * Compatibility Description: Ensures compatibility with VidoRev.
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\VidoRev' ) ) {

    class VidoRev extends ICompatibility {
      protected $id = 'VidoRev';
      protected $title = 'VidoRev';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_VIDOREV';
      protected $description = 'Ensures compatibility with VidoRev.';
      protected $plugin_file = [ 'vidorev-extensions/vidorev-extensions.php' ];

      public function module_init( $sm ) {
        add_filter( 'vidorev_single_video_url', array( $this, 'vidorev_single_video_url' ), 10, 2 );
      }

      /**
       * Syncing the video file.
       *
       * @param $url
       * @param $post_id
       * @return mixed
       */
      public function vidorev_single_video_url( $url, $post_id ) {
        $attachment_id = attachment_url_to_postid( $url );
        Utility::add_media( null, $attachment_id );
        return $url;
      }

    }

  }

}
