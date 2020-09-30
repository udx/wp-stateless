<?php

/**
 * AJAX Handler
 *
 * @since 1.0.0
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

        if (ud_get_stateless_media()->is_connected_to_gs() !== true) {
          throw new \Exception(__('Not connected to GCS', ud_get_stateless_media()->domain));
        }

        @error_reporting(0);

        $id = (int) $_REQUEST['id'];
        $image = get_post($id);

        if (!$image || 'attachment' != $image->post_type || 'image/' != substr($image->post_mime_type, 0, 6))
          throw new \Exception(sprintf(__('Failed resize: %s is an invalid image ID.', ud_get_stateless_media()->domain), esc_html($_REQUEST['id'])));

        if (!current_user_can('manage_options'))
          throw new \Exception(__("Your user account doesn't have permission to resize images", ud_get_stateless_media()->domain));

        $fullsizepath = get_attached_file($image->ID);

        $upload_dir = wp_upload_dir();

        // If no file found
        if (false === $fullsizepath || !file_exists($fullsizepath)) {

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media(apply_filters('wp_stateless_file_name', $fullsizepath, true, "", ""), true, $fullsizepath);

          if ($result_code !== 200) {
            if (!Utility::sync_get_attachment_if_exist($image->ID, $fullsizepath)) {
              throw new \Exception(sprintf(__('Both local and remote files are missing. Unable to resize. (%s)', ud_get_stateless_media()->domain), $image->guid));
            }
          }
        }

        @set_time_limit(-1);

        //
        do_action('sm:pre::synced::image', $id);
        $metadata = wp_generate_attachment_metadata($image->ID, $fullsizepath);

        if (get_post_mime_type($image->ID) !== 'image/svg+xml') {
          if (is_wp_error($metadata)) {
            throw new \Exception($metadata->get_error_message());
          }

          if (empty($metadata)) {
            throw new \Exception(sprintf(__('No metadata generated for %1$s (ID %2$s).', ud_get_stateless_media()->domain), esc_html(get_the_title($image->ID)), $image->ID));
          }
        }

        // If this fails, then it just means that nothing was changed (old value == new value)
        wp_update_attachment_metadata($image->ID, $metadata);
        do_action('sm:synced::image', $id, $metadata);

        return sprintf(__('%1$s (ID %2$s) was successfully synced in %3$s seconds.', ud_get_stateless_media()->domain), esc_html(get_the_title($image->ID)), $image->ID, timer_stop());
      }

      /**
       * @return string
       * @throws \Exception
       */
      public function action_stateless_process_file() {
        @error_reporting(0);

        if (ud_get_stateless_media()->is_connected_to_gs() !== true) {
          throw new \Exception(__('Not connected to GCS', ud_get_stateless_media()->domain));
        }

        $id = (int) $_REQUEST['id'];
        $file = get_post($id);

        if (!$file || 'attachment' != $file->post_type)
          throw new \Exception(sprintf(__('Attachment not found: %s is an invalid file ID.', ud_get_stateless_media()->domain), esc_html($id)));

        if (!current_user_can('manage_options'))
          throw new \Exception(__("You are not allowed to do this.", ud_get_stateless_media()->domain));

        $fullsizepath = get_attached_file($file->ID);
        $local_file_exists = file_exists($fullsizepath);
        $upload_dir = wp_upload_dir();

        if (false === $fullsizepath || !$local_file_exists) {

          // Try get it and save
          $result_code = ud_get_stateless_media()->get_client()->get_media(str_replace(trailingslashit($upload_dir['basedir']), '', $fullsizepath), true, $fullsizepath);

          if ($result_code !== 200) {
            if (!Utility::sync_get_attachment_if_exist($file->ID, $fullsizepath)) {
              throw new \Exception(sprintf(__('File not found (%s)', ud_get_stateless_media()->domain), $file->guid));
            } else {
              $local_file_exists = true;
            }
          } else {
            $local_file_exists = true;
          }
        }

        if ($local_file_exists) {

          if (!ud_get_stateless_media()->get_client()->media_exists(str_replace(trailingslashit($upload_dir['basedir']), '', $fullsizepath))) {

            @set_time_limit(-1);

            $metadata = wp_generate_attachment_metadata($file->ID, $fullsizepath);

            if (is_wp_error($metadata)) {
              throw new \Exception($metadata->get_error_message());
            }

            wp_update_attachment_metadata($file->ID, $metadata);
            do_action('sm:synced::nonImage', $id, $metadata);
          } else {
            // Ephemeral and Stateless modes: we don't need the local version.
            if (ud_get_stateless_media()->get('sm.mode') === 'ephemeral' || ud_get_stateless_media()->get('sm.mode') === 'stateless') {
              unlink($fullsizepath);
            }
          }
        }

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
