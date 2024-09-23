<?php

/**
 * Compatibility Plugin Name: Contact Form by WPForms – Drag & Drop Form Builder for WordPress
 * Compatibility Plugin URI: https://wordpress.org/plugins/wpforms-lite/
 *
 * Compatibility Description: Ensures compatibility with WPForms.
 *
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\WPForms')) {

    class WPForms extends Compatibility {
      const STORAGE_PATH = 'wpforms/';
      const TMP_PATH = self::STORAGE_PATH . 'tmp/';
      const UPLOAD_FIELD_TYPE = 'file-upload';
      const MESSAGE_KEY = 'stateless-wpforms-modern';
      const DISMISSED_MESSAGE_KEY = 'dismissed_notice_' . self::MESSAGE_KEY;

      protected $id = 'wpforms';
      protected $title = 'WPForms';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_WPFORMS';
      protected $description = 'Ensures compatibility with WPForms.';
      protected $plugin_file = ['wpforms-lite/wpforms.php', 'wpforms/wpforms.php'];
      protected $enabled = false;
      protected $is_internal = true;
      protected $filesystem = null;

      public function module_init($sm) {
        if ( !class_exists('\WP_Filesystem_Direct') ) {
          require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
          require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
        }
    
        $this->filesystem = new \WP_Filesystem_Direct( false );

        // exclude randomize_filename from wpforms page
        if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpforms-builder') {
          $this->remove_cache_busting();
        }

        add_action( 'wp_ajax_wpforms_upload_chunk_init', [$this, 'remove_cache_busting'], 5);
        add_action( 'wp_ajax_nopriv_wpforms_upload_chunk_init', [$this, 'remove_cache_busting'], 5);
        add_action( 'wp_ajax_wpforms_submit', [$this, 'remove_cache_busting'], 5);
        add_action( 'wp_ajax_nopriv_wpforms_submit', [$this, 'remove_cache_busting'], 5);
        add_filter( 'sm:sync::syncArgs', [$this, 'sync_args'], 10, 4);
        add_filter( 'wpforms_process_after_filter', [ $this, 'upload_complete' ], 10, 3 );
        add_action( 'wpforms_process_entry_saved', [ $this, 'entry_saved' ], 10, 4 );
        add_filter( 'wpforms_entry_email_data', [ $this, 'entry_email_data' ], 10, 3 );
        add_action( 'wpforms_pre_delete_entries', [ $this, 'pre_delete_entries' ], 10, 1 );
        add_action( 'wpforms_pro_admin_entries_page_empty_trash_before', [ $this, 'before_empty_trash' ], 10, 1 );
        add_filter( 'sm:sync::nonMediaFiles', [$this, 'sync_non_media_files'], 20);
        add_action( 'wpforms_pre_delete_entry_fields', [ $this, 'pre_delete_entry_fields' ], 10, 2 );
        add_action( 'wpforms_builder_save_form', [ $this, 'builder_save_form' ], 10, 2 );
        add_action( 'admin_init', [ $this, 'show_message' ], 10, 2 );
      }

      /**
       * Get the position of 'so-css/' dir in the filename.
       * 
       * @param $name
       * @return bool
       */
      protected function storage_position($name) {
        return strpos($name, self::STORAGE_PATH);
      }

      /**
       * Check if the uploaded file is in the media library.
       * 
       * @return bool
       */
      protected function is_media_library($form_data, $field_id) {
        $field_data = isset( $form_data['fields'] ) && isset( $form_data['fields'][$field_id] ) ? $form_data['fields'][$field_id] : [];

        return isset( $field_data['media_library'] ) && !empty( $field_data['media_library'] );
      }

      /**
       * Skip cache busting for WPForms files.
       */
      public function remove_cache_busting() {
        remove_filter('sanitize_file_name', ['wpCloud\StatelessMedia\Utility', 'randomize_filename'], 10);
      }

      /**
       * Update args when uploading/syncing file to GCS.
       * 
       * @param array $args
       * @param string $name
       * @param string $file
       * @param bool $force
       * 
       * @return array
       */
      public function sync_args($args, $name, $file, $force) {
        if ( $this->storage_position($name) !== 0 ) {
          return $args;
        }

        if ( ud_get_stateless_media()->is_mode('stateless') ) {
          $args['name_with_root'] = false;
        }

        $args['source'] = 'WPForms';
        $args['source_version'] = defined('WPFORMS_VERSION') ? WPFORMS_VERSION : '';

        return $args;
      }

      /**
       * In Stateless mode move the file from /tmp to GCS, so thar WPForms could complete the upload.
       * 
       * @param array $fields    Fields data.
       * @param array $entry     Submitted form entry.
       * @param array $form_data Form data and settings.
       *
       * @return array
       * 
       */
      public function upload_complete($fields, $entry, $form_data) {
        if ( !ud_get_stateless_media()->is_mode('stateless') ) {
          return $fields;
        }

        foreach ( $fields as $field_id => $field ) {
          if ( !isset( $field['type'] ) || $field['type'] !== self::UPLOAD_FIELD_TYPE ) {
            continue;
          }

          // If uploads are saved to the media library
          if ( $this->is_media_library($form_data, $field_id) ) {
            continue;
          }

          $is_visible = ! isset( wpforms()->get( 'process' )->fields[ $field_id ]['visible'] ) || ! empty( wpforms()->get( 'process' )->fields[ $field_id ]['visible'] );

          if ( ! $is_visible ) {
            continue;
          }

          $input_name = sprintf( 'wpforms_%d_%d', absint( $form_data['id'] ), $field_id );
          $file = isset( $_FILES[ $input_name ] ) && !empty( $_FILES[ $input_name ] ) ? $_FILES[ $input_name ] : false;

          // If there was no file uploaded stop here before we continue with the upload process.
          if ( ! $file || $file['error'] !== 0 ) {
            continue;
          }

          $dir = apply_filters('wp_stateless_addon_sync_files_path', '', self::TMP_PATH);
          $filename = $dir . $_FILES[ $input_name ]['name'];

          if ( $this->filesystem->move( $_FILES[ $input_name ]['tmp_name'], $filename, true ) ) {
            $_FILES[ $input_name ]['tmp_name'] = $filename;
          }
        }

        return $fields;
      }

      /**
       * If Media Library is not used for file upload 
       * - move the file to '/wpforms' dir 
       * - run sync
       * - update entry data with the new file path
       */
      public function entry_saved($fields, $entry, $form_data, $entry_id) {
        $wp_uploads_dir = wp_get_upload_dir();

        foreach ( $fields as $field_id => $field ) {
          if ( !isset( $field['type'] ) || $field['type'] !== self::UPLOAD_FIELD_TYPE ) {
            continue;
          }

          if ( !isset( $field['value'] ) || empty($field['value']) ) {
            continue;
          }

          // If uploads are saved to the media library
          if ( $this->is_media_library($form_data, $field_id) ) {
            continue;
          }

          $url = $field['value'];
          $name = str_replace($wp_uploads_dir['baseurl'] . '/', '', $url);
          $absolutePath = apply_filters('wp_stateless_addon_files_root', ''); 
          $absolutePath .= '/' . $name;

          // Move file to the correct location
          if ( ud_get_stateless_media()->is_mode('stateless') ) {
            $source = str_replace( ud_get_stateless_media()->get_gs_host(), ud_get_stateless_media()->get_gs_path(), $url );
            $destination = str_replace( ud_get_stateless_media()->get_gs_host(), ud_get_stateless_media()->get_gs_path(), $absolutePath );

            $this->filesystem->move( $source, $destination, true );
          }

          // Sync non-media file
          $name = apply_filters('wp_stateless_file_name', $name, 0);
          do_action('sm:sync::syncFile', $name, $absolutePath);

          // Update entry data with the new file path
          if ( !ud_get_stateless_media()->is_mode( ['disabled', 'backup'] ) ) {
            $url = ud_get_stateless_media()->is_mode('stateless') 
              ? str_replace( ud_get_stateless_media()->get_gs_path(), ud_get_stateless_media()->get_gs_host(), $absolutePath )
              : str_replace( $wp_uploads_dir['baseurl'], ud_get_stateless_media()->get_gs_host(), $url );

            global $wpdb;

            try {
              $entry_fields = $wpdb->get_var(
                $wpdb->prepare(
                  "SELECT fields FROM {$wpdb->prefix}wpforms_entries WHERE entry_id = %d AND form_id = %d",
                  $entry_id,
                  $form_data['id']
                )
              );

              $entry_fields = json_decode($entry_fields, true);

              if ( isset( $entry_fields[ $field_id ] ) && isset( $entry_fields[ $field_id ]['value'] ) ) {
                $entry_fields[ $field_id ]['value'] = $url;

                $wpdb->update(
                  $wpdb->prefix . 'wpforms_entries',
                  ['fields' => json_encode($entry_fields)],
                  [
                    'entry_id' => $entry_id, 
                    'form_id' => $form_data['id'],
                  ],
                );
              }

              $wpdb->update(
                $wpdb->prefix . 'wpforms_entry_fields',
                ['value' => $url],
                [
                  'entry_id' => $entry_id, 
                  'field_id' => $field_id,
                  'form_id' => $form_data['id'],
                ],
              );
            } catch (\Throwable $e) {
              error_log( $e->getMessage() );
            }

            $fields[$field_id]['value'] = $url;
          }
        }
      }

      /**
       * Update email data with the new file path
       */
      public function entry_email_data($fields, $entry, $form_data) {
        if ( !ud_get_stateless_media()->is_mode('stateless') ) {
          return $fields;
        }

        $wp_uploads_dir = wp_get_upload_dir();

        foreach ( $fields as $field_id => $field ) {
          if ( !isset( $field['type'] ) || $field['type'] !== self::UPLOAD_FIELD_TYPE ) {
            continue;
          }

          // If uploads are saved to the media library
          if ( $this->is_media_library($form_data, $field_id) ) {
            continue;
          }

          $url = $field['value'];
          $name = str_replace($wp_uploads_dir['baseurl'] . '/', '', $url);
          $absolutePath = apply_filters('wp_stateless_addon_files_root', ''); 
          $absolutePath .= '/' . $name;

          $absolutePath = str_replace( ud_get_stateless_media()->get_gs_path(), ud_get_stateless_media()->get_gs_host(), $absolutePath );

          $fields[$field_id]['value'] = $absolutePath;
        }

        return $fields;
      }

      /**
       * Delete files from GCS when deleting entries.
       */
      public function pre_delete_entries($entry_id) {
        global $wpdb;

        try {
          $entry_fields = $wpdb->get_var(
            $wpdb->prepare(
              "SELECT fields FROM {$wpdb->prefix}wpforms_entries WHERE entry_id = %d",
              $entry_id,
            )
          );

          $entry_fields = json_decode($entry_fields, true);

          foreach ( $entry_fields as $field_id => $field ) {
            if ( !isset( $field['type'] ) || $field['type'] !== self::UPLOAD_FIELD_TYPE ) {
              continue;
            }

            // If uploads were saved to the media library
            if ( isset( $field['attachment_id'] ) && !empty($field['attachment_id']) ) {
              continue;
            }

            $url = isset($field['value']) ? $field['value'] : false;

            if ( empty($url) ) {
              continue;
            }

            $name = str_replace( trailingslashit( ud_get_stateless_media()->get_gs_host() ), '', $url);

            do_action('sm:sync::deleteFile', $name);
          }

        } catch (\Throwable $e) {
          error_log( $e->getMessage() );
        }
      }

      /**
       * Delete files from GCS when emptying entries trash.
       */
      public function before_empty_trash($entry_ids) {
        foreach ( $entry_ids as $id ) {
          $this->pre_delete_entries($id);
        }
      }

      /**
       * Filter files created by WPForms for sync.
       * 
       * @param array $file_list
       * @return array
       */
      public function sync_non_media_files($file_list) {
        if ( !method_exists('\wpCloud\StatelessMedia\Utility', 'get_files') ) {
          Helper::log('WP-Stateless version too old, please update.');

          return $file_list;
        }

        $dir = apply_filters('wp_stateless_addon_sync_files_path', '', self::STORAGE_PATH); 

        if (is_dir($dir)) {
          // Getting all the files from dir recursively.
          $files = Utility::get_files($dir);

          // validating and adding to the $files array.
          foreach ($files as $file) {
            if (!file_exists($file)) {
              continue;
            }
    
            // filter temporary logs
            if (strpos($file, self::TMP_PATH) !== false) {
              continue;
            }

            $file = self::STORAGE_PATH . str_replace( $dir, '', wp_normalize_path($file) );
            $file = trim($file, '/');

            if ( !in_array($file, $file_list) ) {
              $file_list[] = $file;
            }
          }
        }

        return $file_list;
      }

      /**
       * Delete files from GCS when file deleted from entry.
       */
      public function pre_delete_entry_fields($row_id, $primary_key) {
        // other cases are handled by other hooks
        if ( $primary_key !== 'id' ) {
          return;
        }

        global $wpdb;

        try {
          // WPForms uses direct database access and ignores caching, we should too
          // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
          $value = $wpdb->get_var(
            $wpdb->prepare(
              "SELECT value FROM {$wpdb->prefix}wpforms_entry_fields WHERE id = %d",
              $row_id,
            )
          );

          // Not a file or not on GCS
          if ( strpos( $value, ud_get_stateless_media()->get_gs_host() ) === false ) {
            return;
          }

          // Not a WPForms file or Media Library file
          if ( $this->storage_position($value) === false ) {
            return;
          }

          $name = str_replace( trailingslashit( ud_get_stateless_media()->get_gs_host() ), '', $value);

          do_action('sm:sync::deleteFile', $name);
        } catch (\Throwable $e) {
          error_log( $e->getMessage() );
        }
      }

      /**
       * Show message in Admin Panel when Modern upload style is used during using Stateless mode
       */
      public function show_message() {
        if ( !ud_get_stateless_media()->is_mode('stateless') ) {
          return;
        }

        $message_option = get_option(self::MESSAGE_KEY, []);

        if ( empty($message_option) ) {
          return;
        }

        $form_names = array_filter( array_values($message_option) );

        if ( empty($form_names) ) {
          return;
        }

        $form_names = implode(', ', $form_names);
        $form_names = '<strong>' . $form_names . '</strong>';

        $message = __('The <em>File Upload</em> field with a <em>Modern</em> style is not compatible with <em>Stateless</em> mode. Change the WPForms field style to <em>Classic</em> or use another WP-Stateless mode with the <em>Modern</em> field style. <strong>Affected Form: %s</strong>', ud_get_stateless_media()->domain);
        $message = sprintf($message, $form_names);

        ud_get_stateless_media()->errors->add([
          'title' => __('WP-Stateless: Problem Detected With WPForms', ud_get_stateless_media()->domain),
          'message' => $message,
          'key' => self::MESSAGE_KEY,
        ], 'warning');
      }

      /**
       * When saving form - show warning for the Modern upload style during using Stateless mode
       */
      public function builder_save_form($form_id, $form_data) {
        $fields = is_array($form_data) && isset( $form_data['fields'] ) ? $form_data['fields'] : [];
        $message_option = get_option(self::MESSAGE_KEY, []);
        $found = false;

        foreach ( $fields as $field ) {
          if ( !isset( $field['type'] ) || $field['type'] !== self::UPLOAD_FIELD_TYPE ) {
            continue;
          }

          if ( isset( $field['style'] ) && $field['style'] == 'modern' ) {
            $found = true;
            break;
          }
        }

        if ( $found ) {
          $title = isset( $form_data['settings']['form_title'] ) 
            ? $form_data['settings']['form_title'] 
            : sprintf( __('Form %d', ud_get_stateless_media()->domain), $form_id);

          $message_option[$form_id] = $title;

          delete_option(self::DISMISSED_MESSAGE_KEY);
        } else {
          unset($message_option[$form_id]);
        }

        if ( empty($message_option) ) {
          delete_option(self::MESSAGE_KEY);
        } else {
          update_option(self::MESSAGE_KEY, $message_option);
        }
      }
    }
  }
}
