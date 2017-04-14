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

    }

  }

}
