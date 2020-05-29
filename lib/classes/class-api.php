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

    /**
     * Class API
     *
     * @package wpCloud\StatelessMedia
     */
    final class API {

      /**
       * Decoded auth_token data
       *
       * @var null|\stdClass
       */
      static private $tokenData = null;

      /**
       * Validate auth token and save data for further processing
       *
       * @param \WP_REST_Request $request
       * @return bool|\WP_Error
       */
      static public function authCheck( \WP_REST_Request $request ) {
        $auth_token = $request->get_header('authorization');
        if ( !$auth_token ) return false;
        try {
          self::$tokenData = Utility::verify_jwt_token( $auth_token );
        } catch ( \Exception $e ) {
          self::$tokenData = null;
          return new \WP_Error( 'auth_failed', $e->getMessage(), ['status' => 401] );
        }
        return true;
      }

      /**
       * API Status Endpoint.
       *
       * @return \WP_REST_Response
       */
      static public function status(): \WP_REST_Response {
        return new \WP_REST_Response( array(
          "ok" => true,
          "message" => "API up.",
          // @todo: remove this
          "test_token" => Utility::generate_jwt_token( ['hello' => 'test'] )
        ), 200 );
      }

      /**
       * Get settings
       *
       * @todo Implement this
       * @param \WP_REST_Request $request
       * @return \WP_REST_Response
       */
      static public function getSettings( \WP_REST_Request $request ) {
        return new \WP_REST_Response(array(
          'setting_1' => 1,
          'setting_2' => 2,
          'data' => self::$tokenData
        ), 200);
      }

      /**
       * Update settings
       *
       * @todo Implement this
       * @param \WP_REST_Request $request
       * @return \WP_REST_Response|\WP_Error
       */
      static public function updateSettings( \WP_REST_Request $request ) {
        if ( self::$tokenData === null || empty( self::$tokenData->user_id ) ) {
          return new \WP_Error( 'unauthorized', 'Auth token looks incorrect', ['status' => 401] );
        }

        return new \WP_REST_Response(array(
          'setting_1' => 1,
          'setting_2' => 2,
          'data' => self::$tokenData,
          'request' => $request->get_json_params()
        ));
      }

    }

  }

}
