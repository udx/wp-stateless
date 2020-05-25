<?php
/**
 * Plugin Name: Advanced Custom Fields: Image Crop Add-on
 * Plugin URI: https://wordpress.org/plugins/acf-image-crop-add-on/
 *
 * Compatibility Description: Ensures compatibility with image cropping and WP-Stateless in the Ephemeral mode.
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\CompatibilityAcfImageCrop' ) ) {

    class CompatibilityAcfImageCrop extends ICompatibility {
      protected $id = 'acf-image-crop';
      protected $title = 'Advanced Custom Fields Image Crop Addon';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_ACFIC';
      protected $description = 'Ensures compatibility with image cropping and WP-Stateless in the Ephemeral mode.';
      protected $plugin_file = [ 'ACF-Image-Crop/acf-image-crop.php', 'acf-image-crop-add-on/acf-image-crop.php' ];
      protected $sm_mode_not_supported = [ 'stateless' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        if( $sm[ 'mode' ] === 'ephemeral' ) {
          /**
           * ACF image crop addons compatibility.
           * We hook into image crops admin_ajax crop request and alter
           * wp_upload_dir() using upload_dir filter.
           * Then we remove the filter once the plugin get the GCS image link.
           *
           */
          add_action( 'wp_ajax_acf_image_crop_perform_crop', array( $this, 'acf_image_crop_perform_crop' ), 1 );

          /*
          * In ephemeral mode no local copy of images is available.
          * So we need to filter full image path before generate_cropped_image() function uses to
          * get image editor using wp_get_image_editor.
          * We will hook into acf-image-crop/full_image_path filter and return GCS link if available.
          *
          */
          add_action( 'acf-image-crop/full_image_path', array( $this, 'acf_image_crop_full_image_path' ), 10, 3 );

        }
      }

      /**
       * Alter wp_upload_dir() using upload_dir filter.
       * Then we remove the filter once the plugin get the GCS image link.
       *
       */
      public function acf_image_crop_perform_crop() {
        add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
        // Removing upload_dir filter.
        add_filter( 'acf-image-crop/filename_postfix', array( $this, 'remove_filter_upload_dir' ) );
      }

      /**
       * Remove upload_dir filter as it's work is done.
       * Used acf-image-crop/filename_postfix as intermediate/temporary hook.
       * We need to remove the upload_dir filter before that function tries to
       * insert attachment to media library. Unless media library would get confused.
       * @param string $postfix
       * @return string
       */
      public function remove_filter_upload_dir( $postfix = '' ) {
        remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );
        return $postfix;
      }

      /**
      * Only for ephemeral mode.
      * Filter image link generate_cropped_image() uses to get image editor.
      * As no local copy of the image is available we need to filter the image path.
      *
      * @param $full_image_path: Expected local image path.
      * @param $id: Image/attachment ID
      * @param $meta_data: Image/attachment meta data.
      *
      * @return GCS link if it has gs_link in meta data.
      */
      public function acf_image_crop_full_image_path( $full_image_path, $id, $meta_data ) {
        if( !empty( $meta_data[ 'gs_link' ] ) ) $full_image_path = $meta_data[ 'gs_link' ];
        return $full_image_path;
      }

      /**
       * Change Upload BaseURL when CDN Used.
       *
       * @param $data
       * @return mixed
       */
      public function upload_dir( $data ) {
        $root_dir = ud_get_stateless_media()->get( 'sm.root_dir' );
        $root_dir = apply_filters("wp_stateless_handle_root_dir", $root_dir);
        $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.
        $data[ 'basedir' ] = ud_get_stateless_media()->get_gs_host() . '/' . $root_dir;
        $data[ 'baseurl' ] = ud_get_stateless_media()->get_gs_host() . '/' . $root_dir;
        $data[ 'url' ] = $data[ 'baseurl' ] . $data[ 'subdir' ];
        return $data;
      }
    }

  }

}
