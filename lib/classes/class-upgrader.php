<?php
/**
 * Upgrader
 *
 * @since 1.2.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Upgrader' ) ) {

    final class Upgrader {

      /**
       * Upgrades data if needed.
       *
       */
      public static function call() {
        /* Maybe upgrade blog ( single site ) */
        self::upgrade();
        /* Maybe upgrade network options */
        if( ud_get_stateless_media()->is_network_detected() ) {
          self::upgrade_network();
        }

      }

      /**
       * Upgrade current blog ( or single site )
       *
       */
      private static function upgrade() {

        $version = get_option( 'wp_sm_version', false );

        /**
         * Upgrade to v.1.2.0 requirements
         */
        if ( !$version || version_compare( $version, '1.2.0', '<' ) ) {

          if( $v = get_option( 'sm.mode' ) ) {
            update_option( 'sm_mode', $v );
            delete_option( 'sm.mode' );
          }

          $v = get_option( 'sm_mode' );
          if( $v == '0' ) update_option( 'sm_mode', 'disabled' );
          elseif( $v == '1' ) update_option( 'sm_mode', 'backup' );
          elseif( $v == '2' ) update_option( 'sm_mode', 'cdn' );

          if( $v = get_option( 'sm.service_account_name' ) ) {
            update_option( 'sm_service_account_name', $v );
            delete_option( 'sm.service_account_name' );
          }

          if( $v = get_option( 'sm.key_file_path' ) ) {
            update_option( 'sm_key_file_path', $v );
            delete_option( 'sm.key_file_path' );
          }

          if( $v = get_option( 'sm.bucket' ) ) {
            update_option( 'sm_bucket', $v );
            delete_option( 'sm.bucket' );
          }

          delete_option( 'sm.app_name' );
          delete_option( 'sm.body_rewrite' );
          delete_option( 'sm.bucket_url_path' );
          delete_option( 'sm.post_content_rewrite' );

        }

        update_option( 'wp_sm_version', ud_get_stateless_media()->args[ 'version' ]  );

      }

      /**
       * Upgrade Network Enabled
       *
       */
      private static function upgrade_network() {

        $version = get_site_option( 'wp_sm_version', false );

        /**
         * Upgrade to v.1.2.0 requirements
         */
        if ( !$version || version_compare( $version, '1.2.0', '<' ) ) {

          if( $v = get_site_option( 'sm.key_file_path' ) ) {
            update_site_option( 'sm_key_file_path', $v );
            delete_site_option( 'sm.key_file_path' );
          }

          if( $v = get_option( 'sm.service_account_name' ) ) {
            update_site_option( 'sm_service_account_name', $v );
            delete_site_option( 'sm.service_account_name' );
          }

        }

        update_site_option( 'wp_sm_version', ud_get_stateless_media()->args[ 'version' ]  );

      }

    }

  }

}