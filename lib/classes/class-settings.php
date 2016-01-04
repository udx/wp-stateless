<?php
/**
 * Settings management and UI
 *
 * @since 0.2.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Settings' ) ) {

    final class Settings extends \UsabilityDynamics\Settings {

      /**
       * Overriden construct
       */
      public function __construct() {

        /* Add 'Settings' link for SM plugin on plugins page. */
        $_basename = plugin_basename( ud_get_stateless_media()->boot_file );

        add_filter( "plugin_action_links_" . $_basename, function( $links ) {
          $settings_link = '<a href="options-media.php#stateless-media">' . __( 'Settings', ud_get_stateless_media()->domain ) . '</a>';
          array_unshift($links, $settings_link);
          return $links;
        });

        parent::__construct( array(
          'store'       => 'options',
          'format'      => 'json',
          'data'        => array(
            'sm' => array(
              'mode' => get_option( 'sm_mode', 'disabled' ),
              'service_account_name' => get_option( 'sm_service_account_name' ),
              'bucket_url_path' => get_option( 'sm_bucket_url_path' ),
              'body_rewrite' => get_option( 'sm_body_rewrite' ),
              'post_content_rewrite' => get_option( 'sm_post_content_rewrite' ),
              'key_file_path' => get_option( 'sm_key_file_path' ),
              'bucket' => get_option( 'sm_bucket' )
            )
          )
        ));

        /**
         * Check for defined constants
         * and Network settings and overwrite current ones if needed.
         */
        if( defined( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT' ) ) {
          $this->set( 'sm.service_account_name', WP_STATELESS_MEDIA_SERVICE_ACCOUNT );
        }
        elseif ( is_multisite() && $service_account_name = get_site_option( 'sm_service_account_name' ) ) {
          $this->set( 'sm.service_account_name', $service_account_name );
        }

        if( defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ) {
          $this->set( 'sm.key_file_path', WP_STATELESS_MEDIA_KEY_FILE_PATH );
        }
        elseif ( is_multisite() && $key_file_path = get_site_option( 'sm_key_file_path' ) ) {
          $this->set( 'sm.key_file_path', $key_file_path );
        }

        /* Use constant value for mode, if set. */
        if( defined( 'WP_STATELESS_MEDIA_MODE' ) ) {
          $this->set( 'sm.mode', WP_STATELESS_MEDIA_MODE );
        }

        /* Use constant value for Bucket, if set. */
        if( defined( 'WP_STATELESS_MEDIA_BUCKET' ) ) {
          $this->set( 'sm.bucket', WP_STATELESS_MEDIA_BUCKET );
        }

        /**
         * Manage specific Network Settings
         */
        if( ud_get_stateless_media()->is_network_detected() || !is_multisite() ) {

          add_filter( 'wpmu_options', array( $this, 'register_network_settings' ) );
          add_action( 'update_wpmu_options', array( $this, 'save_network_settings' ) );
        }

        /** Register options */
        add_action( 'admin_init', array( $this, 'register_settings' ) );
      }

      /**
       * Handles saving network SM data.
       *
       * @action update_wpmu_options
       * @author peshkov@UD
       */
      public function save_network_settings() {
        $settings  = array_map( 'sanitize_text_field', $_POST['sm'] );
        foreach ( $settings as $name => $value ) {
          update_site_option( 'sm_'. $name, $value );
        }
      }

      /**
       * Registers Network Settings in case plugin is Network Enabled.
       *
       * @action wpmu_options
       * @author peshkov@UD
       */
      public function register_network_settings() {
        $san_readonly = defined( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT' ) ? 'readonly="readonly"' : '';
        $kfp_readonly = defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ? 'readonly="readonly"' : '';
        ?>
        <h3><?php _e( 'Stateless Media Settings', ud_get_stateless_media()->domain ); ?></h3>
        <p><?php $this->section_callback(); ?></p>
        <table id="menu" class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e( 'Email Address', ud_get_stateless_media()->domain ); ?></th>
            <td>
              <input <?php echo $san_readonly; ?> id="sm_service_account_name" class="regular-text" size="80" type="text" name="sm[service_account_name]" value="<?php echo esc_attr( $this->get( 'sm.service_account_name' ) ); ?>" />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e( 'Key File path', ud_get_stateless_media()->domain ); ?></th>
            <td>
              <input <?php echo $kfp_readonly; ?> id="sm_key_file_path" class="regular-text" type="text" name="sm[key_file_path]" value="<?php echo esc_attr( $this->get( 'sm.key_file_path' ) ); ?>" />
              <?php if( $kfp_readonly ) { ?>
                <p class="description"><?php _e( 'The key file path can not be changed because it is set via <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code> constant.'); ?></p>
              <?php } ?>
            </td>
          </tr>
        </table>
      <?php
      }

      /**
       * Adds options
       */
      public function register_settings() {

        //** Register Setting */
        register_setting( 'media', 'sm', array( $this, 'validate' ) );

        //** Add Section */
        add_settings_section( 'sm', __( 'Stateless Media', ud_get_stateless_media()->domain ),array( $this, 'section_callback' ), 'media' );

        //** Add Fields */
        add_settings_field( 'sm.mode',  __( 'Mode', ud_get_stateless_media()->domain ),  array( $this, 'sm_fields_mode_callback' ), 'media',  'sm'  );

        //** Add Fields */
        add_settings_field( 'sm.credentials', __( 'Credentials', ud_get_stateless_media()->domain ), array( $this, 'sm_fields_credentials_callback' ), 'media', 'sm' );
        add_settings_field( 'sm.advanced', __( 'Advanced', ud_get_stateless_media()->domain ), array( $this, 'sm_fields_advanced_callback' ), 'media', 'sm' );

        if( defined( 'WP_DEBUG' ) && WP_DEBUG === true || WP_DEBUG === 'true' ) {
          add_settings_field( 'sm.debug', __( 'Debug', ud_get_stateless_media()->domain ), array( $this, 'sm_fields_debug_callback' ), 'media', 'sm' );
        }

      }

      /**
       * Before save filter
       * Used to sync options with options table
       *
       * @param type $input
       * @return type
       */
      public function validate( $input ) {

        if ( !empty( $input ) && is_array( $input ) ) {

          $_has_updates = false;

          foreach( $input as $_field => $_value ) {
            if ( update_option( "sm_{$_field}", $_value ) ) {
              $_has_updates = true;
            }
          }

          if ( $_has_updates ) {
            /* Reset all plugin's transients. */
            ud_get_stateless_media()->flush_transients();
          }

        }

        return $input;
      }

      /**
       * Advanced Media Options
       *
       * @author potanin@UD
       */
      public function sm_fields_advanced_callback() {

        $inputs = array();

        $inputs[] = '<input type="hidden" name="sm[body_rewrite]" value="false" />';
        $inputs[] = '<input type="hidden" name="sm[post_content_rewrite]" value="false" />';
        $inputs[] = '<input type="hidden" name="sm[bucket_url_path]" value="false" />';

        // $inputs[] = '<label for="sm_post_content_rewrite"><input id="sm_post_content_rewrite" type="checkbox" name="sm[post_content_rewrite]" value="true" '. checked( 'true', $this->get( 'sm.post_content_rewrite' ), false ) .'/>'.__( 'Post Content URL Rewrite', ud_get_stateless_media()->domain ).'</label>';
        $inputs[] = '<label for="sm_body_rewrite"><input id="sm_body_rewrite" type="checkbox" name="sm[body_rewrite]" value="true" '. checked( 'true', $this->get( 'sm.body_rewrite' ), false ) .'/>'.__( 'Body content media URL rewrite.', ud_get_stateless_media()->domain ).'</label>';
        // $inputs[] = '<label for="sm_bucket_url_path"><input id="sm_bucket_url_path" type="checkbox" name="sm[bucket_url_path]" value="true" '. checked( 'true', $this->get( 'sm.bucket_url_path' ), false ) .' />'.__( 'Bucket CNAME Path', ud_get_stateless_media()->domain ).'</label>';

        echo '<section class="wp-stateless-media-options wp-stateless-media-advanced-options"><p>' . implode( "</p>\n<p>", (array) apply_filters( 'sm::settings::advanced', $inputs ) ) . '</p></section>';

      }

      /**
       *
       * @author potanin@UD
       */
      public function sm_fields_debug_callback() {

        echo( '<pre>' . print_r( json_decode($this->get()), true ) . '</pre>' );

      }

      /**
       * Render Credential Inputs
       *
       */
      public function sm_fields_credentials_callback() {

        $inputs = array( '<section class="wp-stateless-media-options wp-stateless-credentials-options">' );

        if( !defined( 'WP_STATELESS_MEDIA_BUCKET' ) ) {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Bucket', ud_get_stateless_media()->domain ).'</label><input id="sm_bucket" class="regular-text" type="text" name="sm[bucket]" value="'. esc_attr( $this->get( 'sm.bucket' ) ) .'" /></p>';
        } else {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Bucket', ud_get_stateless_media()->domain ).'</label><input id="sm_bucket" class="regular-text" readonly="readonly" type="text" name="sm[bucket]" value="'. esc_attr( $this->get( 'sm.bucket' ) ) .'" /></p>';
          // $inputs[ ] = '<p class="description">' . __( 'The Bucket can not be changed because it is set via <code>WP_STATELESS_MEDIA_BUCKET</code> constant.' ) . '</p>';
        }

        if( ud_get_stateless_media()->is_network_detected() ) {

          if( is_super_admin() ) {

            $san_readonly = get_site_option( 'sm_service_account_name' ) || defined( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT' ) ? 'readonly="readonly"' : '';
            $kfp_readonly = get_site_option( 'sm_key_file_path' ) || defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ? 'readonly="readonly"' : '';

            $inputs[] = '<p class="wp-stateless-media-for-superadmin"><label for="sm_service_account_name">'.__( 'Email Address', ud_get_stateless_media()->domain ).'</label><input ' . $san_readonly . ' id="sm_service_account_name" size="80" class="regular-text sm_service_account_name" type="text" name="sm[service_account_name]" value="'. esc_attr( $this->get( 'sm.service_account_name' ) ) .'" /></p>';

            if( $san_readonly ) {
              if( defined( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT' ) ) {
                $inputs[ ] = '<p class="description">' . __( 'The account name can not be changed because it is set via <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT</code> constant.' ) . '</p>';
              } else {
                $inputs[ ] = '<p class="description">' . sprintf( __( 'The account name can not be changed because it is set via <a href="%s">Network Settings.</a>' ), network_admin_url( 'settings.php' ) ) . '</p>';
              }

            }

            $inputs[] = '<p class="wp-stateless-media-for-superadmin"><label for="sm_key_file_path">'.__( 'Key File path', ud_get_stateless_media()->domain ).'</label><input ' . $kfp_readonly . ' id="sm_key_file_path" class="regular-text sm_key_file_path" type="text" name="sm[key_file_path]" value="'. esc_attr( $this->get( 'sm.key_file_path' ) ) .'" /></p>';

            if( $kfp_readonly ) {
              if( defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ) {
                $inputs[ ] = '<p class="description">' . __( 'The key file path can not be changed because it is set via <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code> constant.' ) . '</p>';
              } else {
                $inputs[ ] = '<p class="description">' . sprintf( __( 'The key file path can not be changed because it is set via <a href="%s">Network Settings.</a>' ), network_admin_url( 'settings.php' ) ) . '</p>';
              }
            }

          }

        } else {

          $inputs[] = '<p><label for="sm_service_account_name">'.__( 'Email Address', ud_get_stateless_media()->domain ).'</label><input autocomplete="off" id="sm_service_account_name" class="regular-text sm_service_account_name" size="80" type="text" name="sm[service_account_name]" value="'. esc_attr( $this->get( 'sm.service_account_name' ) ) .'" /></p>';
          $inputs[] = '<p><label for="sm_key_file_path">'.__( 'Key File path', ud_get_stateless_media()->domain ).'</label><input id="sm_key_file_path" class="regular-text sm_key_file_path" type="text" name="sm[key_file_path]" value="'. esc_attr( $this->get( 'sm.key_file_path' ) ) .'" /></p>';

        }

        $inputs[] = '</section>';

        echo implode( "\n", (array) apply_filters( 'sm::settings::credentials', $inputs ) );


      }

      /**
       * Render inputs
       */
      public function sm_fields_mode_callback() {

        $inputs = array(
          '<p class="sm-mode"><label for="sm_mode_disabled"><input id="sm_mode_disabled" '. checked( 'disabled', $this->get( 'sm.mode' ), false ) .' type="radio" name="sm[mode]" value="disabled" />'.__( 'Disabled', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Disable Stateless Media.', ud_get_stateless_media()->domain).'</small></label></p>',
          '<p class="sm-mode"><label for="sm_mode_backup"><input id="sm_mode_backup" '. checked( 'backup', $this->get( 'sm.mode' ), false ) .' type="radio" name="sm[mode]" value="backup" />'.__( 'Backup', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Push media files to Google Storage but keep using local ones.', ud_get_stateless_media()->domain).'</small></label></p>',
          '<p class="sm-mode"><label for="sm_mode_cdn"><input id="sm_mode_cdn" '. checked( 'cdn', $this->get( 'sm.mode' ), false ) .' type="radio" name="sm[mode]" value="cdn" />'.__( 'CDN', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Push media files to Google Storage and use them directly from there.', ud_get_stateless_media()->domain).'</small></label></p>'
        );

        echo implode( "\n", (array)apply_filters( 'sm::settings::mode', $inputs ) );

      }

      /**
       * Description callback
       */
      public function section_callback() {
        echo '<p id="stateless-media">' . __( 'Google Storage credentials and settings.', ud_get_stateless_media()->domain ) . '</p>';

        //Imagick is installed
        if( !extension_loaded('imagick') || !class_exists("Imagick" ) )  {
          echo '<p id="stateless-media">' . __( 'Be advised, Imagick does not seem to be installed, thumbnails will not be generated not uploaded..', ud_get_stateless_media()->domain ) . '</p>';
        }

        // Check GD library.
        if ( !extension_loaded('gd') || !function_exists('gd_info') ) {
          echo '<p id="stateless-media">' . __( 'Be advised, GD does not seem to be installed, thumbnails will not be generated not uploaded..', ud_get_stateless_media()->domain ) . '</p>';
        }

      }

      /**
       * Wrapper for setting value.
       * @param string $key
       * @param bool $value
       * @param bool $bypass_validation
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = '', $value = false, $bypass_validation = false ) {

        if (  $value !== false ) {
          update_option( str_replace( '.', '_', $key ), $value );
        }

        return parent::set( $key, $value, $bypass_validation );

      }

    }

  }

}