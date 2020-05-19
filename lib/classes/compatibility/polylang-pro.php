<?php
/**
 * Theme Name: Polylang Pro
 * Plugin URI: https://polylang.pro
 *
 * Compatibility Description: Ensures compatibility with Polylang Pro.
 * https://github.com/wpCloud/wp-stateless/issues/378
 *
 */

namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\Polylang' ) ) {

    class Polylang extends ICompatibility {
      protected $id = 'polylang-pro';
      protected $title = 'Polylang Pro';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_POLYLANG_PRO';
      protected $description = 'Ensures compatibility with Polylang Pro.';
      protected $plugin_file = [ 'polylang-pro/polylang.php' ];

      /**
       * @param $sm
       */
      public function module_init( $sm ) {
        add_action( 'pll_translate_media', array( $this, 'pll_translate_media' ), 10, 3 );
      }

      /**
       * @param $post_id
       * @param $tr_id
       * @param $lang_slug
       */
      public function pll_translate_media( $post_id, $tr_id, $lang_slug ) {
        // We need to delay the metadata update until the metadata is fully generated.
        add_filter( 'wp_stateless_media_synced', function ( $metadata, $attachment_id, $force, $args ) use ( $post_id, $tr_id, $lang_slug ) {
          if( $attachment_id == $post_id ) {
            $meta = get_post_meta( $tr_id, '_wp_attachment_metadata', true );
            if( !empty( $meta[ 'sizes' ] ) ) {
              // with Polylang Pro 2.6 the sizes of original image gets missing.
              update_post_meta( $attachment_id, '_wp_attachment_metadata', wp_slash( $meta ) );
              return $meta;
            } else if( !empty( $metadata[ 'sizes' ] ) ) {
              // But user reported that metadata gets missing on duplicate.
              update_post_meta( $tr_id, '_wp_attachment_metadata', wp_slash( $metadata ) );
              return $metadata;
            }
          }
          return $metadata;
        }, 10, 4 );
      }

    }

  }

}
