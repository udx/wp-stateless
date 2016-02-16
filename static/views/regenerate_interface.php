<?php
  /**
   * Stateless Sync Interface
   */
  global $wpdb;
?>

<div class="wrap">

  <h2><?php _e('Stateless Images Synchronisation', ud_get_stateless_media()->domain); ?></h2>

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <p><?php _e( 'Regenerate local and remote thumbnails and synchronize local and remote storage.', ud_get_stateless_media()->domain ); ?></p>

  <?php

  if ( ! $images = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC" ) ) {
    echo '	<p>' . sprintf( __( "Unable to find any images. Are you sure <a href='%s'>some exist</a>?", ud_get_stateless_media()->domain ), admin_url( 'upload.php?post_mime_type=image' ) ) . "</p></div>";
  }

  echo '<pre>';
  print_r( $images );
  echo '</pre>';

  ?>
</div>