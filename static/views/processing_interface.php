<div class="wrap">

  <div id="errors" class="stateless-admin-notice admin-error">
    <strong><?php _e('Errors encountered. Try reloading the page.', ud_get_stateless_media()->domain); ?></strong>
    <ul></ul>
  </div>

  <div class="metabox-holder processes-holder">
    <p class="processing-hint"><strong><?php _e('Hint', ud_get_stateless_media()->domain) ?>:</strong> <?php _e('You can close this page once processing is started.', ud_get_stateless_media()->domain) ?></p>
    
    <div class="postbox-container">
      <?php foreach ($processes as $process) : ?>

        <div class="postbox" data-id="<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $process->id); ?>">

          <div class="postbox-header">
            <h2 class="hndle">
              <div class="title-holder"><?php echo $process->name; ?></div>

              <span>
                <span title="<?php _e('Processing in progress...', ud_get_stateless_media()->domain) ?>" class="loading dashicons dashicons-update"></span>

                <?php if ($process->helper) : ?>
                  <a href="javascript:;" data-position='{"edge":"left","align":"center"}' data-title="<?php echo $process->helper['title']; ?>" data-text="<?php echo $process->helper['content']; ?>" class="pointer dashicons dashicons-info"></a>
                <?php endif; ?>
              </span>
            </h2>
          </div>

          <div class="inside">
            <ul>
              <li><strong><?php _e('Total Items', ud_get_stateless_media()->domain) ?>:</strong> <span><?php echo $process->total_items; ?></span></li>
            </ul>

            <?php if ($process->allow_limit) : ?>
              <div class="options">
                <label><?php _e('Enable Limit', ud_get_stateless_media()->domain) ?> <input type="checkbox" class="limit_enabled"/></label>

                <label class="limit_field">
                  <input type="number" style="width:80px" />
                </label>
              </div>
            <?php endif; ?>

            <?php if ($process->allow_sorting) : ?>
              <div class="options">
                <label>
                  <?php _e('Start from', ud_get_stateless_media()->domain) ?>
                  <select class="order_value">
                    <option value="desc" selected><?php _e('newest', ud_get_stateless_media()->domain) ?></option>
                    <option value="asc"><?php _e('oldest', ud_get_stateless_media()->domain) ?></option>
                  </select>
                </label>
              </div>
            <?php endif; ?>

            <div class="progress">
              <div class="bar-wrapper">
                <div class="legend">
                  <strong class="total"><?php _e('Total', ud_get_stateless_media()->domain) ?>: <span></span></strong>
                  <strong class="queued"><?php _e('Queued', ud_get_stateless_media()->domain) ?>: <span></span></strong>
                  <strong class="processed"><?php _e('Processed', ud_get_stateless_media()->domain) ?>: <span></span></strong>
                </div>
                
                <div class="bar total">
                  <div class="bar queued">
                    <div class="bar processed">&nbsp;</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="progress-notice"></div>

            <div class="actions">
              <button type="button" class="button button-primary disabled"><?php _e('Run', ud_get_stateless_media()->domain) ?></button>
              <button type="button" class="button button-secondary disabled"><?php _e('Stop', ud_get_stateless_media()->domain) ?></button>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
