<?php

/**
 * AJAX Handler
 */
namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\Ajax')) {

    final class Ajax {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'stateless_process_image',
        'stateless_process_file',
        'stateless_get_bucket_folder'
      );

      /**
       * The list of wp_ajax_nopriv_{name} actions
       *
       * @var array
       */
      var $nopriv_actions = array();

      /**
       * Init AJAX actions
       *
       * @author peshkov@UD
       */
      public function __construct() {
        foreach ($this->actions as $action) {
          add_action('wp_ajax_' . $action, array($this, 'request'));
        }

        foreach ($this->nopriv_actions as $action) {
          add_action('wp_ajax_nopriv_' . $action, array($this, 'request'));
        }
      }

      /**
       * Handles AJAX request
       *
       * @author peshkov@UD
       */
      public function request() {
        global $doing_manual_sync;

        $response = array(
          'message' => '',
          'html' => '',
        );

        try {
          $doing_manual_sync = true;

          $action = $_REQUEST['action'];

          /** Determine if the current class has the method to handle request */
          if (is_callable(array($this, 'action_' . $action))) {
            $response = call_user_func_array(array($this, 'action_' . $action), array($_REQUEST));
          }
          /** Determine if external function exists to handle request */
          elseif (is_callable('action_' . $action)) {
            $response = call_user_func_array($action, array($_REQUEST));
          } elseif (is_callable($action)) {
            $response = call_user_func_array($action, array($_REQUEST));
          }
          /** Oops! */
          else {
            throw new \Exception(__('Incorrect Request'));
          }
        } catch (\Exception $e) {
          wp_send_json_error($e->getMessage());
        }

        wp_send_json_success($response);
      }

      /**
       * Regenerate image sizes.
       */
      public function action_stateless_process_image() {
        @error_reporting(0);
        @set_time_limit(-1);

        $image = Utility::process_image_by_id(intval($_REQUEST['id']));

        return sprintf(__('%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($image->ID)), $image->ID, timer_stop());
      }

      /**
       * @return string
       * @throws \Exception
       */
      public function action_stateless_process_file() {
        @error_reporting(0);
        @set_time_limit(-1);

        $file = Utility::process_file_by_id(intval($_REQUEST['id']));

        return sprintf(__('%1$s (ID %2$s) was successfully synchronised in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($file->ID)), $file->ID, timer_stop());
      }

      /**
       * Returns bucket folder (to check whether there is something to continue in JS)
       */
      public function action_stateless_get_bucket_folder() {
        return array('bucket_folder'  => get_option('sm_root_dir'));
      }
    }
  }
}
