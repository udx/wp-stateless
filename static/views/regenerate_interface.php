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

<div class="wrap">

  <h2><?php _e('Stateless Images Synchronisation', ud_get_stateless_media()->domain); ?></h2>

  <noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', ud_get_stateless_media()->domain ); ?></em></p></noscript>

  <p><?php _e( 'Regenerate local and remote thumbnails and synchronize local and remote storage.', ud_get_stateless_media()->domain ); ?></p>

  <div id="regenthumbs-bar" style="position:relative;height:25px;">
    <div id="regenthumbs-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
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
    // <![CDATA[
    jQuery(document).ready(function($){
      var i;
      var rt_images = [<?php echo $ids; ?>];
      var rt_total = rt_images.length;
      var rt_count = 1;
      var rt_percent = 0;
      var rt_successes = 0;
      var rt_errors = 0;
      var rt_failedlist = '';
      var rt_resulttext = '';
      var rt_timestart = new Date().getTime();
      var rt_timeend = 0;
      var rt_totaltime = 0;
      var rt_continue = true;

      // Create the progress bar
      $("#regenthumbs-bar").progressbar();
      $("#regenthumbs-bar-percent").html( "0%" );

      // Stop button
      $("#regenthumbs-stop").click(function() {
        rt_continue = false;
        $('#regenthumbs-stop').val("<?php _e( 'Stopping...', ud_get_stateless_media()->domain ); ?>");
      });

      // Clear out the empty list element that's there for HTML validation purposes
      $("#regenthumbs-debuglist li").remove();

      // Called after each resize. Updates debug information and the progress bar.
      function RegenThumbsUpdateStatus( id, success, response ) {
        $("#regenthumbs-bar").progressbar( "value", ( rt_count / rt_total ) * 100 );
        $("#regenthumbs-bar-percent").html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
        rt_count = rt_count + 1;

        if ( success ) {
          rt_successes = rt_successes + 1;
          $("#regenthumbs-debug-successcount").html(rt_successes);
          $("#regenthumbs-debuglist").append("<li>" + response.success + "</li>");
        }
        else {
          rt_errors = rt_errors + 1;
          rt_failedlist = rt_failedlist + ',' + id;
          $("#regenthumbs-debug-failurecount").html(rt_errors);
          $("#regenthumbs-debuglist").append("<li>" + response.error + "</li>");
        }
      }

      // Called when all images have been processed. Shows the results and cleans up.
      function RegenThumbsFinishUp() {
        rt_timeend = new Date().getTime();
        rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );

        $('#regenthumbs-stop').hide();

        if ( rt_errors > 0 ) {
          rt_resulttext = '<?php _e('All done, but some errors appeared.', ud_get_stateless_media()->domain); ?>';
        } else {
          rt_resulttext = '<?php _e('All done without errors.', ud_get_stateless_media()->domain); ?>';
        }

        $("#message").html("<p><strong>" + rt_resulttext + "</strong></p>");
        $("#message").show();
      }

      // Regenerate a specified image via AJAX
      function RegenThumbs( id ) {
        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: { action: "stateless_process_image", id: id },
          success: function( response ) {
            if ( response !== Object( response ) || ( typeof response.success === "undefined" && typeof response.error === "undefined" ) ) {
              response = new Object;
              response.success = false;
              response.error = "<?php printf( esc_js( __( 'The resize request was abnormally terminated (ID %s). This is likely due to the image exceeding available memory or some other type of fatal error.', 'regenerate-thumbnails' ) ), '" + id + "' ); ?>";
            }

            if ( response.success ) {
              RegenThumbsUpdateStatus( id, true, response );
            }
            else {
              RegenThumbsUpdateStatus( id, false, response );
            }

            if ( rt_images.length && rt_continue ) {
              RegenThumbs( rt_images.shift() );
            }
            else {
              RegenThumbsFinishUp();
            }
          },
          error: function( response ) {
            RegenThumbsUpdateStatus( id, false, response );

            if ( rt_images.length && rt_continue ) {
              RegenThumbs( rt_images.shift() );
            }
            else {
              RegenThumbsFinishUp();
            }
          }
        });
      }

      RegenThumbs( rt_images.shift() );
    });
    // ]]>
  </script>
</div>