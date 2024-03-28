<?php

/**
 * Compatibility Plugin Name: Polylang Pro
 * Compatibility Plugin URI: https://polylang.pro
 *
 * Compatibility Description: Ensures compatibility with Polylang Pro.
 * https://github.com/wpCloud/wp-stateless/issues/378
 *
 */

namespace wpCloud\StatelessMedia {

  if (!class_exists('wpCloud\StatelessMedia\Polylang')) {

    class Polylang extends Compatibility {
      protected $id = 'polylang-pro';
      protected $title = 'Polylang Pro';
      protected $constant = 'WP_STATELESS_COMPATIBILITY_POLYLANG_PRO';
      protected $description = 'Ensures compatibility with Polylang Pro.';
      protected $plugin_file = ['polylang-pro/polylang.php'];
      protected $enabled = false;
      protected $is_internal = true;

      /**
       * @param $sm
       */
      public function module_init($sm) {
        // Polylang duplicates attachments for all languages used.
        // But WP generates image sizes only for one of the attachment copies (currently second attachment).
        // Thus image sizes and WP Stateless meta data appears to be broken for other copies. 
        add_action('pll_translate_media', array($this, 'pll_translate_media'), 10, 3);
      }

      private function get_stateless_meta($post_id) {
        // In case Polylang is not active of codebase not compatible anymore
        if (!function_exists('pll_get_post_translations')) {
          return null;
        }
    
        $metadata = null;
    
        // Get other attachment ids for the same file
        $ids = pll_get_post_translations($post_id);
 
        // Search for the first attachment with WP Stateless meta
        foreach ($ids as $id) {
          $meta = get_post_meta($id, 'sm_cloud', true);
    
          if ( !empty($meta) && !empty($meta['name']) ) {
            $metadata = $meta;
            break;
          }
        }
    
        return $metadata;
      }

      private function get_stateless_data($post_id) {
        // In case Polylang is not active of codebase not compatible anymore
        if (!function_exists('pll_get_post_translations')) {
          return null;
        }

        // For the compatibility with the older versions of WP Stateless
        if ( !function_exists('ud_stateless_db') ) {
          return null;
        }
    
        $ids = pll_get_post_translations($post_id);

        $data = [];
    
        foreach ($ids as $id) {
          $file =  apply_filters( 'wp_stateless_get_file', [], $id );

          if ( !empty($file) && !empty($file['name']) ) {
            $data['file'] = $file;
            
            break;
          }
        }

        foreach ($ids as $id) {
          $sizes = apply_filters( 'wp_stateless_get_file_sizes', [], $id );

          if ( !empty($sizes) ) {
            $data['sizes'] = $sizes;
            
            break;
          }
        }

        foreach ($ids as $id) {
          $meta = apply_filters( 'wp_stateless_get_file_meta', [], $id );

          if ( !empty($meta) ) {
            $data['meta'] = $meta;
            
            break;
          }
        }

        return $data;
      }

      /**
       * @param $post_id
       * @param $tr_id
       * @param $lang_slug
       */
      public function pll_translate_media($post_id, $tr_id, $lang_slug) {
        // We need to delay the metadata update until the metadata is fully generated.
        add_filter('wp_stateless_media_synced', function ($metadata, $attachment_id, $force, $args) use ($post_id, $tr_id, $lang_slug) {
          if ($attachment_id == $post_id) {
            $meta = get_post_meta($tr_id, '_wp_attachment_metadata', true);
          
            if (!empty($meta['sizes'])) {
              // with Polylang Pro 2.6 the sizes of original image gets missing.
              update_post_meta($attachment_id, '_wp_attachment_metadata', wp_slash($meta));
          
              $metadata = $meta;
            } else if (!empty($metadata['sizes'])) {
              // But user reported that metadata gets missing on duplicate.
              update_post_meta($tr_id, '_wp_attachment_metadata', wp_slash($metadata));
            }
          }

          // Duplicate WP Stateless meta for the new attachment
          $cloud_meta = $this->get_stateless_meta($attachment_id);

          if ( !empty($cloud_meta) ) {
            // Need to update cloud meta for both original attachment
            update_post_meta($attachment_id, 'sm_cloud', wp_slash($cloud_meta));
            // Update duplicated attachment meta
            update_post_meta($tr_id, 'sm_cloud', wp_slash($cloud_meta)); 
          }
      
          // Duplicate WP Stateless data for the new attachment and sizes
          $data = $this->get_stateless_data($attachment_id);

          if ( !empty($data) ) {
            try {
              foreach ( [$attachment_id, $tr_id] as $id ) {
                do_action('wp_stateless_set_file', $id, $data['file']);

                $sizes = $data['sizes'] ?? [];

                foreach ($sizes as $name => $size) {
                  do_action('wp_stateless_set_file_size', $id, $name, $size);
                }

                $meta = $data['meta'] ?? [];

                foreach ($meta as $key => $value) {
                  do_action('wp_stateless_set_file_meta', $id, $key, $value);
                }
              }
            } catch (\Throwable $e) {
              error_log( $e->getMessage() );
            }
          }

          return $metadata;
        }, 10, 4);
      }
    }
  }
}
