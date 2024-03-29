<?php
/**
 * Plugin Name: EWWW Image Optimizer
 * Plugin URI: https://ewww.io/
 *
 * Compatibility Description: Enables support for these EWWW Image Optimizer Image Optimizer features
 *
 * https://github.com/wpCloud/wp-stateless/issues/371
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\EWWW' ) ) {

    class EWWW extends Compatibility {
      protected $id = 'ewww';
      protected $title = 'EWWW Image Optimizer';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_EWWW';
      protected $description = 'Enables limited support for EWWW Image Optimizer in CDN mode.';
      protected $plugin_file = [ 'ewww-image-optimizer/ewww-image-optimizer.php' ];
      protected $sm_mode_not_supported = [ 'stateless' ];
      protected $enabled = false;
      protected $is_internal = true;

      public function module_init( $sm ) {
        // add_filter( 'ewww_image_optimizer_pre_optimization', array($this, 'pre_optimization'), 10, 3 );
        add_action( 'ewww_image_optimizer_post_optimization', array( $this, 'post_optimization' ), 10, 3 );
      }

      /**
       * Try to restore images before compression
       *
       * @param $file
       * @param $type
       * @param $fullsize
       */
      public function pre_optimization( $file, $type, $fullsize ) {
        // wp_stateless_file_name filter will remove the basedir from the path and prepend with root dir.
        $name = apply_filters( 'wp_stateless_file_name', $file );
        do_action( 'sm:sync::syncFile', $name, $file, true, array( 'ephemeral' => false, 'download' => true, 'use_root' => true ) );
      }

      /**
       * If image size not exist then upload it to GS.
       *
       * @param $file
       * @param $type
       * @param $fullsize
       */
      public function post_optimization( $file, $type, $fullsize ) {
        // wp_stateless_file_name filter will remove the basedir from the path and prepend with root dir.
        $name = apply_filters( 'wp_stateless_file_name', $file );
        do_action( 'sm:sync::syncFile', $name, $file, true, array( 'use_root' => true ) );


        // if($fullsize && file_exists($file . '.bak'))
        //     do_action( 'sm:sync::syncFile', $name . '.bak', $file . '.bak', true);

        if( file_exists( $file . '.webp' ) ) {
          add_filter( 'upload_mimes', array( $this, 'add_webp_mime' ), 10, 2 );
          do_action( 'sm:sync::syncFile', $name . '.webp', $file . '.webp', true, array( 'use_root' => true ) );
          remove_filter( 'upload_mimes', array( $this, 'add_webp_mime' ), 10 );
        }
      }

    }

  }

}
