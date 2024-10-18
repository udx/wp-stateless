
<div class="metabox-holder">
  <div class="postbox">
	  <h2 class="hndle stateless-info-heading">
      <?php _e('Info', ud_get_stateless_media()->domain); ?>
      <div>
        <span class="stateless-info-copy-success"><?php _e('Copied!', ud_get_stateless_media()->domain); ?></span>
        <button type="button" class="button copy-button" data-clipboard-text="<?php echo $copy_text; ?>"><?php _e('Copy Info to Clipboard', ud_get_stateless_media()->domain); ?></button>
      </div>
    </h2>

    <div class="inside">
      <div class="main">

        <div id="stateless-info">

          <?php foreach( $sections as $key => $section ) : ?>

            <h3 class="stateless-info-heading">
              <button class="stateless-info-button" data-section="stateless-info-block-<?php echo $key; ?>" type="button">
                <span class="title"><?php echo $section->title; ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
              </button>
            </h3>

            <div id="stateless-info-block-<?php echo $key; ?>" class="stateless-info-block hidden"\>
              <table class="widefat striped" role="presentation">
                <tbody>

                <?php foreach( $section->rows as $key => $row ) : ?>
                    <tr class="<?php echo $key; ?>">
                      <td class="label"><?php echo $row->label; ?></td>
                      <td class="value"><?php echo $row->value; ?></td>
                    </tr>
                  <?php endforeach; ?>

                </tbody>
              </table>
            </div>

          <?php endforeach; ?>
        
        </div> <!-- id="stateless-info" -->

      </div> <!-- class="main" -->
    </div> <!-- class="inside" -->

  </div>
</div>
