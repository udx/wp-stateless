<?php
/**
 * API Handler
 *
 *
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
       * @param $request
       * @return array
       */
      static public function getSettings( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

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
       * @param $request
       * @return array
       */
      static public function getMediaLibrary( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

        return array(
            "ok" => true,
            "message" => "getMediaLibrary endpoint.",
            "mediaLibrary" => array()
        );

      }

      /**
       * Get media item Endpoint.
       *
       * @param $request
       * @return array
       */
      static public function getMediaItem( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

        return array(
            "ok" => true,
            "message" => "getMediaItem endpoint.",
            "mediaItem" => array()
        );

      }

      /**
       * Handle Auth.
       *
       * @param $request
       * @return bool
       */
      static public function authRequest( $request = false ) {

        //die( '<pre>' . print_r($request->get_param('key'),true) . '</pre>' );

        if( !$request ) {
          return false;
        }

        if( !$request->get_param('key') ) {
          return false;
        }

        $settings = apply_filters('stateless::get_settings', array());

        if( !$settings[ 'api_key' ] ) {
          return false;
        }

        if( $request->get_param('key') !== $settings[ 'api_key' ] ) {
          return false;
        }

        return true;


      }

    }

  }

}
