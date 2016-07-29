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
       * @var false|null|string
       */
      private $regenerate_ui = null;

      /**
       * Overriden construct
       */
      public function __construct() {

        add_action('admin_menu', array( $this, 'admin_menu' ));

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
              'bucket' => get_option( 'sm_bucket' ),
              'root_dir' => get_option( 'sm_root_dir' ),
              'key_json' => get_option( 'sm_key_json' ),
              'body_rewrite' => get_option( 'sm_body_rewrite' ),
              'on_fly' => get_option( 'sm_on_fly' ),
              'delete_remote' => get_option( 'sm_delete_remote' ),
              'hashify_file_name' => get_option( 'sm_hashify_file_name' ),
              'override_cache_control' => get_option( 'sm_override_cache_control' ),
              'cache_control' => get_option( 'sm_cache_control' )
            )
          )
        ));

        /**
         * MODE
         */
        /* Use Network setting for mode if needed. */
        $network_mode = get_site_option( 'sm_mode', 'false' );
        if( $network_mode && $network_mode != 'false' ) {
          $this->set( 'sm.mode', $network_mode );
        }

        /* Use constant value for mode, if set. */
        if( defined( 'WP_STATELESS_MEDIA_MODE' ) ) {
          $this->set( 'sm.mode', WP_STATELESS_MEDIA_MODE );
        }

        /**
         * BUCKET
         */

        /* Use constant value for Bucket, if set. */
        if( defined( 'WP_STATELESS_MEDIA_BUCKET' ) ) {
          $this->set( 'sm.bucket', WP_STATELESS_MEDIA_BUCKET );
        }

        /**
         * ROOT DIR
         */

        /* Use constant value for Root Dir, if set. */
        if( defined( 'WP_STATELESS_MEDIA_ROOT_DIR' ) ) {
          $this->set( 'sm.root_dir', WP_STATELESS_MEDIA_ROOT_DIR );
        }

        /**
         * DELETE REMOTE
         */

        $network_delete_remote = get_site_option( 'sm_delete_remote', '0' );
        if( $network_delete_remote && $network_delete_remote == '1' ) {
          $this->set( 'sm.delete_remote', 'true' );
        }
        if( defined( 'WP_STATELESS_MEDIA_DELETE_REMOTE' ) ) {
          $this->set( 'sm.delete_remote', WP_STATELESS_MEDIA_DELETE_REMOTE );
        }

        /**
         * HASH FILENAME
         */

        $network_hashify = get_site_option( 'sm_hashify_file_name', '0' );
        if( $network_hashify && $network_hashify == '1' ) {
          $this->set( 'sm.hashify_file_name', 'true' );
        }
        if( defined( 'WP_STATELESS_MEDIA_HASH_FILENAME' ) ) {
          $this->set( 'sm.hashify_file_name', WP_STATELESS_MEDIA_HASH_FILENAME );
        }

        /* Set default cacheControl in case it is empty */
        $cache_control = trim( $this->get( 'sm.cache_control' ) );
        if ( empty( $cache_control ) ) {
          $this->set( 'sm.cache_control', 'public, max-age=36000, must-revalidate' );
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
       * Refresh settings
       */
      public function refresh() {
        $this->set('sm', array(
          'mode' => get_option( 'sm_mode', 'disabled' ),
          'bucket' => get_option( 'sm_bucket' ),
          'root_dir' => get_option( 'sm_root_dir' ),
          'key_json' => get_option( 'sm_key_json' ),
          'body_rewrite' => get_option( 'sm_body_rewrite' ),
          'on_fly' => get_option( 'sm_on_fly' ),
          'delete_remote' => get_option( 'sm_delete_remote' ),
          'hashify_file_name' => get_option( 'sm_hashify_file_name' ),
          'override_cache_control' => get_option( 'sm_override_cache_control' ),
          'cache_control' => get_option( 'sm_cache_control' )
        ));
      }

      /**
       * Add menu options
       */
      public function admin_menu() {
        $this->regenerate_ui = add_management_page( __( 'Stateless Images Synchronisation', ud_get_stateless_media()->domain ), __( 'Stateless Sync', ud_get_stateless_media()->domain ), 'manage_options', 'stateless-regenerate', array($this, 'regenerate_interface') );
      }

      /**
       * Draw interface
       */
      public function regenerate_interface() {
        include ud_get_stateless_media()->path( '/static/views/regenerate_interface.php', 'dir' );
      }

      /**
       * Handles saving network SM data.
       *
       * @action update_wpmu_options
       * @author peshkov@UD
       */
      public function save_network_settings() {
        $settings  = $_POST['sm'];
        foreach ( $settings as $name => $value ) {
          update_site_option( 'sm_'. $name, stripslashes($value) );
        }
      }

      /**
       * Registers Network Settings in case plugin is Network Enabled.
       *
       * @action wpmu_options
       * @author peshkov@UD
       */
      public function register_network_settings() {
        ?>
        <h3><?php _e( 'Stateless Media Settings', ud_get_stateless_media()->domain ); ?></h3>
        <p><?php $this->section_callback(); ?></p>
        <div class="key_type"><label><b><?php _e('Service Account JSON', ud_get_stateless_media()->domain) ?></b></label>
          <div class="_key_type _sm_key_json">
            <textarea id="sm_key_json" class="field regular-textarea sm_key_json" type="text" name="sm[key_json]"><?php echo get_site_option( 'sm_key_json' ); ?></textarea>
          </div>
        </div>

        <div class="sm_mode">
         <label><b><?php _e('Mode', ud_get_stateless_media()->domain); ?></b></label>
          <?php

            $_mode = get_site_option( 'sm_mode' );

            $inputs = array(
              '<p class="sm-mode"><label for="sm_mode_override"><input id="sm_mode_override" '. checked( 'false', $_mode, false ) .' type="radio" name="sm[mode]" value="false" />'.__( 'Do not override', ud_get_stateless_media()->domain ).''
              . '<small class="description">'.__('Do not override site settings by network settings.', ud_get_stateless_media()->domain).'</small></label></p>',
              '<p class="sm-mode"><label for="sm_mode_disabled"><input id="sm_mode_disabled" '. checked( 'disabled', $_mode, false ) .' type="radio" name="sm[mode]" value="disabled" />'.__( 'Disabled', ud_get_stateless_media()->domain ).''
              . '<small class="description">'.__('Disable Stateless Media.', ud_get_stateless_media()->domain).'</small></label></p>',
              '<p class="sm-mode"><label for="sm_mode_backup"><input id="sm_mode_backup" '. checked( 'backup', $_mode, false ) .' type="radio" name="sm[mode]" value="backup" />'.__( 'Backup', ud_get_stateless_media()->domain ).''
              . '<small class="description">'.__('Push media files to Google Storage but keep using local ones.', ud_get_stateless_media()->domain).'</small></label></p>',
              '<p class="sm-mode"><label for="sm_mode_cdn"><input id="sm_mode_cdn" '. checked( 'cdn', $_mode, false ) .' type="radio" name="sm[mode]" value="cdn" />'.__( 'CDN', ud_get_stateless_media()->domain ).''
              . '<small class="description">'.__('Push media files to Google Storage and use them directly from there.', ud_get_stateless_media()->domain).'</small></label></p>'
            );
            echo implode( "\n", (array)apply_filters( 'sm::network::settings::mode', $inputs ) );
          ?>
        </div>

        <div class="sm_advanced">
          <label><b><?php _e('Advanced', ud_get_stateless_media()->domain); ?></b></label>
          <?php

          $_delete_remote = get_site_option( 'sm_delete_remote' );

          $inputs = array();
          $inputs[] = '<p><input type="hidden" name="sm[delete_remote]" value="0" />';
          $inputs[] = '<label for="sm_delete_remote"><input id="sm_delete_remote" type="checkbox" name="sm[delete_remote]" value="1" '. checked( '1', $_delete_remote, false ) .'/>'.__( 'Delete media from GCS when media is deleted from the site.', ud_get_stateless_media()->domain ).'<small> '.__( '(This option may slow down media deletion process)', ud_get_stateless_media()->domain ).'</small></label></p>';

          $_hashify_file_name = get_site_option( 'sm_hashify_file_name' );

          $inputs[] = '<p><input type="hidden" name="sm[hashify_file_name]" value="0" />';
          $inputs[] = '<label for="sm_hashify_file_name"><input id="sm_hashify_file_name" type="checkbox" name="sm[hashify_file_name]" value="1" '. checked( '1', $_hashify_file_name, false ) .'/>'.__( 'Randomize the filename of newly uploaded media files.', ud_get_stateless_media()->domain ).'<small> '.__( '(May help to avoid unwanted GCS caching)', ud_get_stateless_media()->domain ).'</small></label></p>';

          echo implode( "\n", (array)apply_filters( 'sm::network::settings::advanced', $inputs ) );
          ?>
        </div>
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

        // override cache control
        $inputs[] = '<input type="hidden" name="sm[override_cache_control]" value="false" />';
        $inputs[] = '<label for="sm_override_cache_control"><input id="sm_override_cache_control" type="checkbox" name="sm[override_cache_control]" value="true" '. checked( 'true', $this->get( 'sm.override_cache_control' ), false ) .'/>'.__( 'Override default Cache Control', ud_get_stateless_media()->domain ).'<small> '.__( '(Use input bellow to change cache control)', ud_get_stateless_media()->domain ).'</small></label>';

        // cache control input
        $inputs[] = '<input id="sm_cache_control" class="regular-text" type="text" name="sm[cache_control]" value="'.$this->get( 'sm.cache_control' ).'" />';

        // body content rewrite
        $inputs[] = '<input type="hidden" name="sm[body_rewrite]" value="false" />';
        $inputs[] = '<label for="sm_body_rewrite"><input id="sm_body_rewrite" type="checkbox" name="sm[body_rewrite]" value="true" '. checked( 'true', $this->get( 'sm.body_rewrite' ), false ) .'/>'.__( 'Body content media URL rewrite.', ud_get_stateless_media()->domain ).'</label>';

        // on fly generate
        $inputs[] = '<input type="hidden" name="sm[on_fly]" value="false" />';
        $inputs[] = '<label for="sm_on_fly"><input id="sm_on_fly" type="checkbox" name="sm[on_fly]" value="true" '. checked( 'true', $this->get( 'sm.on_fly' ), false ) .'/>'.__( 'Upload on-fly generated (by third-party scripts) images to GCS.', ud_get_stateless_media()->domain ).'<small> '.__( '(This option may slow down file upload processes)', ud_get_stateless_media()->domain ).'</small></label>';

        // delete remote
        $network_delete_remote = get_site_option( 'sm_delete_remote', '0' );
        $inputs[] = '<input type="hidden" name="sm[delete_remote]" value="false" />';
        $inputs[] = '<label for="sm_delete_remote"><input '. disabled( true, $network_delete_remote == '1', false ) .' title="'.($network_delete_remote == '1'?__('This option cannot be changed because it is set in Network Settings', ud_get_stateless_media()->domain):'').'" id="sm_delete_remote" type="checkbox" name="sm[delete_remote]" value="true" '. checked( 'true', $this->get( 'sm.delete_remote' ), false ) .'/>'.__( 'Delete media from GCS when media is deleted from the site.', ud_get_stateless_media()->domain ).'<small> '.__( '(This option may slow down media deletion process)', ud_get_stateless_media()->domain ).'</small></label>';

        // hashify
        $network_hashify = get_site_option( 'sm_hashify_file_name', '0' );
        $inputs[] = '<input type="hidden" name="sm[hashify_file_name]" value="false" />';
        $inputs[] = '<label for="sm_hashify_file_name"><input '. disabled( true, $network_hashify == '1', false ) .' title="'.($network_hashify == '1'?__('This option cannot be changed because it is set in Network Settings', ud_get_stateless_media()->domain):'').'" id="sm_hashify_file_name" type="checkbox" name="sm[hashify_file_name]" value="true" '. checked( 'true', $this->get( 'sm.hashify_file_name' ), false ) .'/>'.__( 'Randomize the filename of newly uploaded media files.', ud_get_stateless_media()->domain ).'<small> '.__( '(May help to avoid unwanted GCS caching)', ud_get_stateless_media()->domain ).'</small></label>';

        echo '<section class="wp-stateless-media-options wp-stateless-media-advanced-options"><p>' . implode( "</p>\n<p>", (array) apply_filters( 'sm::settings::advanced', $inputs ) ) . '</p></section>';

      }

      /**
       * Debug output
       * @author potanin@UD
       */
      public function sm_fields_debug_callback() {

        echo( '<pre style="width:600px;overflow:scroll;">' . print_r( json_decode($this->get()), true ) . '</pre>' );

      }

      /**
       * Render Credential Inputs
       *
       */
      public function sm_fields_credentials_callback() {

        $network_key = false;
        $inputs = array( '<section class="wp-stateless-media-options wp-stateless-credentials-options">' );

        if( !defined( 'WP_STATELESS_MEDIA_BUCKET' ) ) {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Bucket', ud_get_stateless_media()->domain ).'</label><div><input id="sm_bucket" class="regular-text" type="text" name="sm[bucket]" value="'. esc_attr( $this->get( 'sm.bucket' ) ) .'" /></div></p>';
        } else {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Bucket', ud_get_stateless_media()->domain ).'</label><div><input id="sm_bucket" class="regular-text" readonly="readonly" type="text" name="sm[bucket]" value="'. esc_attr( $this->get( 'sm.bucket' ) ) .'" /></div></p>';
        }

        if( !defined( 'WP_STATELESS_MEDIA_ROOT_DIR' ) ) {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Root Directory', ud_get_stateless_media()->domain ).'<small> '.__('(With trailing slash!)', ud_get_stateless_media()->domain).'</small></label><div><input id="sm_bucket" class="regular-text" type="text" name="sm[root_dir]" value="'. esc_attr( $this->get( 'sm.root_dir' ) ) .'" /></div></p>';
        } else {
          $inputs[ ] = '<p><label for="sm_bucket">'.__( 'Root Directory', ud_get_stateless_media()->domain ).'<small> '.__('(With trailing slash!)', ud_get_stateless_media()->domain).'</small></label><div><input id="sm_bucket" class="regular-text" readonly="readonly" type="text" name="sm[root_dir]" value="'. esc_attr( $this->get( 'sm.root_dir' ) ) .'" /></div></p>';
        }

        if( ud_get_stateless_media()->is_network_detected() ) {

          if( is_super_admin() ) {

            $network_key = get_site_option( 'sm_key_json' );

            $kjsn_readonly = $network_key || defined( 'WP_STATELESS_MEDIA_KEY_FILE_PATH' ) ? 'readonly="readonly"' : '';

            $inputs[] = '<div class="key_type"><label>'.__('Service Account JSON', ud_get_stateless_media()->domain).'</label>';
            $inputs[] = '<div class="_key_type _sm_key_json">';
            $inputs[] = '<textarea '.$kjsn_readonly.' id="sm_key_json" class="field regular-textarea sm_key_json" type="text" name="sm[key_json]" >'. esc_attr( $network_key ? $network_key : $this->get( 'sm.key_json' ) ) .'</textarea>';
            $inputs[] = '</div>';
            $inputs[] = '</div>';

            if( $kjsn_readonly ) {
              $inputs[ ] = '<p class="description">' . sprintf( __( 'The account name can not be changed because it is set via <a href="%s">Network Settings.</a>' ), network_admin_url( 'settings.php' ) ) . '</p>';
            }

          }

        } else {

          $inputs[] = '<div class="key_type"><label>'.__('Service Account JSON', ud_get_stateless_media()->domain).'</label>';
            $inputs[] = '<div class="_key_type _sm_key_json">';
              $inputs[] = '<textarea id="sm_key_json" class="field regular-textarea sm_key_json" type="text" name="sm[key_json]" >'. esc_attr( $this->get( 'sm.key_json' ) ) .'</textarea>';
            $inputs[] = '</div>';
          $inputs[] = '</div>';
        }

        $inputs[] = '</section>';

        echo implode( "\n", (array) apply_filters( 'sm::settings::credentials', $inputs ) );

      }

      /**
       * Render inputs
       */
      public function sm_fields_mode_callback() {

        $network_mode = get_site_option( 'sm_mode', 'false' );
        $_mode = $network_mode && $network_mode != 'false' ? $network_mode : $this->get( 'sm.mode' );

        $inputs = array(
          '<p class="sm-mode"><label for="sm_mode_disabled"><input '. disabled( true, $network_mode != 'false', false ) .'  id="sm_mode_disabled" '. checked( 'disabled', $_mode, false ) .' type="radio" name="sm[mode]" value="disabled" />'.__( 'Disabled', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Disable Stateless Media.', ud_get_stateless_media()->domain).'</small></label></p>',
          '<p class="sm-mode"><label for="sm_mode_backup"><input '. disabled( true, $network_mode != 'false', false ) .' id="sm_mode_backup" '. checked( 'backup', $_mode, false ) .' type="radio" name="sm[mode]" value="backup" />'.__( 'Backup', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Push media files to Google Storage but keep using local ones.', ud_get_stateless_media()->domain).'</small></label></p>',
          '<p class="sm-mode"><label for="sm_mode_cdn"><input '. disabled( true, $network_mode != 'false', false ) .' id="sm_mode_cdn" '. checked( 'cdn', $_mode, false ) .' type="radio" name="sm[mode]" value="cdn" />'.__( 'CDN', ud_get_stateless_media()->domain ).''
          . '<small class="description">'.__('Push media files to Google Storage and use them directly from there.', ud_get_stateless_media()->domain).'</small></label></p>'
        );

        if( $network_mode != 'false' ) {
          $inputs[] = '<p class="description">' . sprintf( __( 'Mode cannot be changed because it is set via <a href="%s">Network Settings.</a>' ), network_admin_url( 'settings.php' ) ) . '</p>';
        }

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