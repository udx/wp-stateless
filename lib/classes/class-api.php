<?php
/**
 * API Handler
 *
 * @since 1.0.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\API' ) ) {

    final class API {

      /**
       * API Status Endpoint.
       *
       * @return array
       */
      static public function status() {

        return array(
          "ok" => true,
          "message" => "API up."
        );

      }

      /**
       * Jobs Endpoint.
       *
       * @return array
       */
      static public function jobs() {

        return array(
          "ok" => true,
          "message" => "Job endpoint up.",
          "jobs" => array()
        );

      }

      /**
       * Get settings Endpoint.
       *
       * @return array
       */
      static public function getSettings() {

        $settings = apply_filters('stateless::get_settings', array());

        return array(
            "ok" => true,
            "message" => "getSettings endpoint.",
            "settings" => $settings
        );

      }

      /**
       * Get media library Endpoint.
       *
       * @return array
       */
      static public function getMediaLibrary() {

        return array(
            "ok" => true,
            "message" => "getMediaLibrary endpoint.",
            "mediaLibrary" => array()
        );

      }

      /**
       * Get media item Endpoint.
       *
       * @return array
       */
      static public function getMediaItem() {

        return array(
            "ok" => true,
            "message" => "getMediaItem endpoint.",
            "mediaItem" => array()
        );

      }

    }

  }

}
