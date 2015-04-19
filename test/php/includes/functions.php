<?php
/**
 * 
 * 
 */
 
/**
 * Removes the post type and its taxonomy associations.
 */
function _unregister_post_type( $cpt_name ) {
  unset( $GLOBALS['wp_post_types'][ $cpt_name ] );
  unset( $GLOBALS['_wp_post_type_features'][ $cpt_name ] );

  foreach ( $GLOBALS['wp_taxonomies'] as $taxonomy ) {
    if ( false !== $key = array_search( $cpt_name, $taxonomy->object_type ) ) {
      unset( $taxonomy->object_type[$key] );
    }
  }
}

function get_echo($callable, $args = array()) {
  ob_start();
  call_user_func_array($callable, $args);
  return ob_get_clean();
}
