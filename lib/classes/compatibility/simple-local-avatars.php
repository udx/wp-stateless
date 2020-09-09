<?php
/**
 * Plugin Name: Simple Local Avatars
 * Plugin URI: https://wordpress.org/plugins/simple-local-avatars/
 *
 * Compatibility Description: Ensures compatibility with Simple Local Avatars plugin.
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\SimpleLocalAvatars')) {

    /**
     * Class SimpleLocalAvatars
     * @package wpCloud\StatelessMedia
     */
    class SimpleLocalAvatars extends ICompatibility {
      protected $id = 'simple-local-avatars';
      protected $title = 'Simple Local Avatars';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_SLA';
      protected $description = 'Ensures compatibility with Simple Local Avatars plugin.';
      protected $plugin_file = 'simple-local-avatars/simple-local-avatars.php';
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * Initialize compatibility module
       *
       * @param $sm
       */
      public function module_init( $sm ) {
        // Only makes sense in CDN or Ephemeral modes
        if ( in_array( $sm['mode'], ['cdn', 'ephemeral'] ) ) {
          add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 4 );
        }
      }

      /**
       * Filter the result of specific user meta to redirect avatar images to GCS if in CDN or Stateless
       *
       * @param $null
       * @param $object_id
       * @param $meta_key
       * @param $_
       * @return mixed
       */
      public function get_user_metadata( $null, $object_id, $meta_key, $_ ) {
        // Get out if not the meta we are interested in
        if ( $meta_key !== 'simple_local_avatar' ) return $null;

        // Remove THIS filter to avoid the infinite recursion
        remove_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10 );

        // Get the actual meta
        $user_meta = get_user_meta( $object_id, $meta_key );

        // Add THIS filter back for future calls
        add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 4 );

        // Get GCS link and local upload url
        $image_host = ud_get_stateless_media()->get_gs_host();
        $bucketLink = apply_filters('wp_stateless_bucket_link', $image_host);
        $upload     = wp_get_upload_dir();

        // Replace local urls with corresponding GCS urls
        if ( !empty( $user_meta[0] ) && is_array( $user_meta[0] ) ) {
          foreach ( $user_meta[0] as $key => &$value ) {
            if ( is_numeric( $key ) ) {
              $value = trailingslashit( $bucketLink ) . apply_filters( 'wp_stateless_file_name', str_replace( $upload['baseurl'], '', $value ), true );
            }
          }
        }

        // Return filtered data back
        return $user_meta;
      }
    }
  }
}