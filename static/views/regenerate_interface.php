<?php
  /**
   * Stateless Sync Interface
   */
  global $wpdb;

  if ( wp_script_is( 'jquery-ui-widget', 'registered' ) )
    wp_enqueue_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path('static/scripts/jquery-ui/jquery.ui.progressbar.min.js', 'url'), array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.8.6' );
  else
    wp_enqueue_script( 'jquery-ui-progressbar', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/jquery.ui.progressbar.min.1.7.2.js', 'url' ), array( 'jquery-ui-core' ), '1.7.2' );

  wp_enqueue_style( 'jquery-ui-regenthumbs', ud_get_stateless_media()->path( 'static/scripts/jquery-ui/redmond/jquery-ui-1.7.2.custom.css', 'url' ), array(), '1.7.2' );
?>

<div id="message" class="updated fade" style="display:none"></div>

<div class="wrap">

  <h2><?php _e('Stateless Images Synchronisation', ud_get_stateless_media()->domain); ?></h2>

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <p><?php _e( 'Regenerate local and remote thumbnails and synchronize local and remote storage.', ud_get_stateless_media()->domain ); ?></p>

  <div id="regenthumbs-bar" style="position:relative;height:25px;">
    <div id="regenthumbs-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
  </div>

  <ol id="regenthumbs-debuglist">
    <li style="display:none"></li>
  </ol>

  <div>
    <label>
      <input type="radio" name="action" value="regenerate_images" checked="checked" />
      <?php _e( 'Regenerate all stateless images and synchronize Google Storage with local server', ud_get_stateless_media()->domain ); ?>
    </label>
  </div>

  <div>
    <label>
      <input type="radio" name="action" value="sync_non_images" />
      <?php _e( 'Synchronize non-images files between Google Storage and local server', ud_get_stateless_media()->domain ); ?>
    </label>
  </div>

  <div>
    <button class="button-primary" id="go"><?php _e( 'Go! (may take a while)' ); ?></button>
  </div>

  <?php

  if ( ! $images = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC" ) ) {
    echo '	<p>' . sprintf( __( "Unable to find any images. Are you sure <a href='%s'>some exist</a>?", ud_get_stateless_media()->domain ), admin_url( 'upload.php?post_mime_type=image' ) ) . "</p></div>";
  }

  $ids = array();
  foreach ( $images as $image )
    $ids[] = $image->ID;
  $ids = implode( ',', $ids );

  ?>

  <script type="text/javascript">
    var rt_images = [<?php echo $ids; ?>];
  </script>
</div>