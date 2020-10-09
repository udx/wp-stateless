<?php

/**
 * API Handler
 *
 *
 *
 * @since 1.0.0
 */

namespace wpCloud\StatelessMedia {

  use wpCloud\StatelessMedia\Sync\FileSync;
  use wpCloud\StatelessMedia\Sync\ImageSync;
  use wpCloud\StatelessMedia\Sync\NonLibrarySync;

  if (!class_exists('wpCloud\StatelessMedia\API')) {

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
      static public function authCheck(\WP_REST_Request $request) {
        $auth_token = $request->get_header('authorization');
        // Allow using custom `x-wps-auth` header if authorization hedaer is disabled
        if (!$auth_token) $auth_token = $request->get_header('x-wps-auth');

        if (!$auth_token) return false;

        try {
          self::$tokenData = Utility::verify_jwt_token($auth_token);
        } catch (\Exception $e) {
          self::$tokenData = null;
          return new \WP_Error('auth_failed', $e->getMessage(), ['status' => 401]);
        }
        return true;
      }

      /**
       * API Status Endpoint.
       *
       * @return \WP_REST_Response
       */
      static public function status() {
        return new \WP_REST_Response(array(
          "ok" => true,
          "message" => "API up."
        ), 200);
      }

      /**
       * Get settings
       *
       * @todo Implement this if needed
       * @param \WP_REST_Request $request
       * @return \WP_REST_Response|\WP_Error
       */
      static public function getSettings(\WP_REST_Request $request) {
        return new \WP_Error('not_implemented', 'Method not implemented', ['status' => 501]);
      }

      /**
       * Update settings
       *
       * @param \WP_REST_Request $request
       * @return \WP_REST_Response|\WP_Error
       */
      static public function updateSettings(\WP_REST_Request $request) {
        if (self::$tokenData === null || empty(self::$tokenData->user_id)) {
          return new \WP_Error('unauthorized', 'Auth token looks incorrect', ['status' => 401]);
        }
        $is_gae                 = isset($_SERVER["GAE_VERSION"]) ? true : false;
        $upload_dir             = wp_upload_dir();
        $is_upload_dir_writable = is_writable($upload_dir['basedir']);

        try {
          $queryParams = $request->get_json_params();
          if (empty($queryParams)) throw new \Exception('Query is empty');

          $bucketName = isset($queryParams['bucket_name']) ? $queryParams['bucket_name'] : null;
          $privateKeyData = isset($queryParams['private_key_data']) ? $queryParams['private_key_data'] : null;

          if (!$bucketName || !$privateKeyData) {
            throw new \Exception('bucket_name and private_key_data are required');
          }

          if ($privateKeyData) {
            $privateKeyData = base64_decode($privateKeyData);
          }

          switch (self::$tokenData->is_network) {
            case true:
              if (!user_can(self::$tokenData->user_id, 'manage_network_options')) {
                return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
              }
              /**
               * If Google App Engine detected - set Stateless mode
               * and Google App Engine compatibility by default
               */
              if ($is_gae || !$is_upload_dir_writable) {
                update_site_option('sm_mode', 'stateless');

                $modules = get_site_option('stateless-modules', array());
                if ($is_gae && empty($modules['google-app-engine']) || $modules['google-app-engine'] != 'true') {
                  $modules['google-app-engine'] = 'true';
                  update_site_option('stateless-modules', $modules);
                }
              } elseif (get_site_option('sm_mode', 'disabled') == 'disabled') {
                update_site_option('sm_mode', 'ephemeral');
              }
              update_site_option('sm_bucket', $bucketName);
              update_site_option('sm_key_json', $privateKeyData);
              break;

            case false:
              if (!user_can(self::$tokenData->user_id, 'manage_options')) {
                return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
              }
              /**
               * If Google App Engine detected - set Stateless mode
               * and Google App Engine compatibility by default
               */
              if ($is_gae || !$is_upload_dir_writable) {
                update_option('sm_mode', 'stateless');

                $modules = get_option('stateless-modules', array());
                if ($is_gae && empty($modules['google-app-engine']) || $modules['google-app-engine'] != 'true') {
                  $modules['google-app-engine'] = 'true';
                  update_option('stateless-modules', $modules);
                }
              } elseif (get_option('sm_mode', 'disabled') == 'disabled') {
                update_option('sm_mode', 'ephemeral');
              }
              update_option('sm_bucket', $bucketName);
              update_option('sm_key_json', $privateKeyData);
              break;
          }

          return new \WP_REST_Response(array(
            'ok' => true,
            'message' => 'Settings updated successfully'
          ));
        } catch (\Throwable $e) {
          return new \WP_Error('internal_server_error', $e->getMessage(), ['status' => 500]);
        }
      }

      /**
       * Get all available processes with their states
       * 
       * @return \WP_REST_Response|\WP_Error
       */
      static public function syncGetProcesses() {
        try {
          if (!user_can(self::$tokenData->user_id, 'manage_options')) {
            return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
          }

          return new \WP_REST_Response(array(
            'ok' => true,
            'data' => Utility::get_available_sync_classes()
          ));
        } catch (\Throwable $e) {
          return new \WP_Error('internal_server_error', $e->getMessage(), ['status' => 500]);
        }
      }

      /**
       * Get a single process by id (base64 encoded class name)
       * 
       * @param \WP_REST_Request $request
       * @return \WP_Error|\WP_REST_Response
       */
      static public function syncGetProcess(\WP_REST_Request $request) {
        try {
          if (!user_can(self::$tokenData->user_id, 'manage_options')) {
            return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
          }

          $id = base64_decode($request->get_param('id'));
          if (!class_exists($id)) {
            throw new \Exception(sprintf('Could not get process by id %s', $id));
          }

          $syncClasses = Utility::get_available_sync_classes();

          if (!array_key_exists($id, $syncClasses)) {
            throw new \Exception(sprintf('Could not get process by id %s', $id));
          }

          return new \WP_REST_Response(array(
            'ok' => true,
            'data' => $syncClasses[$id]
          ));
        } catch (\Throwable $e) {
          return new \WP_Error('internal_server_error', $e->getMessage(), ['status' => 500]);
        }
      }

      /**
       * Run sync by processing class id
       * 
       * @param \WP_REST_Request $request
       * @return \WP_Error|\WP_REST_Response
       */
      static public function syncRun(\WP_REST_Request $request) {
        try {
          if (!user_can(self::$tokenData->user_id, 'manage_options')) {
            return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
          }

          $params = wp_parse_args($request->get_params(), [
            'id' => null,
            'limit' => null,
            'order' => null,
          ]);

          if (empty($params['id']) || !class_exists($params['id'])) {
            throw new \Exception(sprintf('Processing class not found: %s', $params['id']));
          }

          $processingClass = $params['id'];

          return new \WP_REST_Response(array(
            'ok' => $processingClass::instance()->start($params)
          ));
        } catch (\Throwable $e) {
          return new \WP_Error('internal_server_error', $e->getMessage(), ['status' => 500]);
        }
      }

      /**
       * Stop sync by processing class id
       * 
       * @param \WP_REST_Request $request
       * @return \WP_Error|\WP_REST_Response
       */
      static public function syncStop(\WP_REST_Request $request) {
        try {
          if (!user_can(self::$tokenData->user_id, 'manage_options')) {
            return new \WP_Error('not_allowed', 'Sorry, you are not allowed to perform this action', ['status' => 403]);
          }

          $params = wp_parse_args($request->get_params(), [
            'id' => null
          ]);

          if (empty($params['id']) || !class_exists($params['id'])) {
            throw new \Exception(sprintf('Processing class not found: %s', $params['id']));
          }

          $processingClass = $params['id'];

          return new \WP_REST_Response(array(
            'ok' => $processingClass::instance()->stop()
          ));
        } catch (\Throwable $e) {
          return new \WP_Error('internal_server_error', $e->getMessage(), ['status' => 500]);
        }
      }
    }
  }
}
