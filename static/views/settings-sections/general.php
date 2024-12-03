<tr>
  <th scope="row"><?php _e('General', ud_get_stateless_media()->domain); ?></th>
  
  <td>
    <fieldset>
    
      <legend class="screen-reader-text"><span><?php _e('General', ud_get_stateless_media()->domain); ?></span></legend>
      
      <h4><?php _e('Mode', ud_get_stateless_media()->domain); ?></h4>
      
      <p class="description"><strong id="notice-mode"></strong></p>

      <?php if (is_network_admin()) : ?>
        <p class="sm-mode">
          <label for="sm_mode_not_override">
            <input id="sm_mode_not_override" type="radio" name="sm[mode]" value="" <?php checked( $sm->mode, '' ); ?>>
            <?php _e('Don\'t override', ud_get_stateless_media()->domain); ?>
            <small class="description"><?php _e('Don\'t override.', ud_get_stateless_media()->domain); ?></small>
          </label>
        </p>
      <?php endif; ?>

      <p class="sm-mode">
        <label for="sm_mode_disabled">
          <input id="sm_mode_disabled" type="radio" name="sm[mode]" value="disabled" <?php checked( $sm->mode, 'disabled' ); ?>>
          <?php _e('Disabled', ud_get_stateless_media()->domain); ?>
          <small class="description"><?php _e('Disable Stateless Media.', ud_get_stateless_media()->domain); ?></small>
        </label>
      </p>

      <p class="sm-mode">
        <label for="sm_mode_backup">
          <input id="sm_mode_backup" type="radio" name="sm[mode]" value="backup" <?php checked( $sm->mode, 'backup' ); ?>>
          <?php _e('Backup', ud_get_stateless_media()->domain); ?>
          <small class="description"><?php _e('Upload media files to Google Storage and serve local file urls.', ud_get_stateless_media()->domain); ?></small>
        </label>
      </p>
      
      <p class="sm-mode">
        <label for="sm_mode_cdn">
          <input id="sm_mode_cdn" type="radio" name="sm[mode]" value="cdn" <?php checked( $sm->mode, 'cdn' ); ?>>
          <?php _e('CDN', ud_get_stateless_media()->domain); ?>
          <small class="description"><?php _e('Copy media files to Google Storage and serve them directly from there.', ud_get_stateless_media()->domain); ?></small>
        </label>
      </p>
      
      <p class="sm-mode">
        <label for="sm_mode_ephemeral">
          <input id="sm_mode_ephemeral" type="radio" name="sm[mode]" value="ephemeral" <?php checked( $sm->mode, 'ephemeral' ); ?>>
          <?php _e('Ephemeral', ud_get_stateless_media()->domain); ?>
          <small class="description"><?php _e('Store and serve media files with Google Cloud Storage only. Media files are not stored locally, but local storage is used temporarily for processing and is required for certain compatibilities, generating thumbnails for PDF documents.', ud_get_stateless_media()->domain); ?></small>
        </label>
      </p>
      
      <p class="sm-mode">
        <label for="sm_mode_stateless">
          <input id="sm_mode_stateless" type="radio" name="sm[mode]" value="stateless" <?php checked( $sm->mode, 'stateless' ); ?>>
          <?php if ( apply_filters('wp_stateless_is_app_engine', false) ) : ?>
            <?php _e('Stateless (Google App Engine Detected)', ud_get_stateless_media()->domain); ?>
          <?php else : ?>
            <?php _e('Stateless', ud_get_stateless_media()->domain); ?>
          <?php endif; ?>

          <small class="description"><?php _e('Store and serve media files with Google Cloud Storage only. Media files are not stored locally.', ud_get_stateless_media()->domain); ?></small>
        </label>
      </p>

      <hr>

      <h4><?php _e('File URL Replacement', ud_get_stateless_media()->domain); ?></h4>
      
      <p class="sm-file-url">
        <select name="sm[body_rewrite]" id="sm_body_rewrite">
          <?php if (is_network_admin()) : ?>
            <option value="" <?php selected( $sm->body_rewrite, '' ); ?>><?php _e("Don't override"); ?></option>
          <?php endif; ?>
          <option value="false" <?php selected( $sm->body_rewrite, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
          <option value="enable_editor" <?php selected( $sm->body_rewrite, 'enable_editor' ); ?>><?php _e('Enable Editor', ud_get_stateless_media()->domain); ?></option>
          <option value="enable_meta" <?php selected( $sm->body_rewrite, 'enable_meta' ); ?>><?php _e('Enable Meta', ud_get_stateless_media()->domain); ?></option>
          <option value="true" <?php selected( $sm->body_rewrite, 'true' ); ?>><?php _e('Enable Editor & Meta', ud_get_stateless_media()->domain); ?></option>
        </select>  
      </p>
            
      <p class="description">
        <strong id="notice-body_rewrite"></strong> 
        <?php _e('Scans post content and meta during presentation and replaces local media file urls with GCS urls. When selecting meta or true depending on the amount of meta, this could be significantly impact performance negatively. This setting does not modify your database.', ud_get_stateless_media()->domain); ?>
      </p>

      <h4 class="supported-file-types"><?php _e('Supported File Types', ud_get_stateless_media()->domain); ?></h4>
      
      <div class="body_rewrite_types supported-file-types">
        <p>
          <label for="body_rewrite_types">
            <input name="sm[body_rewrite_types]" type="text" id="body_rewrite_types" class="regular-text ltr" value="<?php echo $sm->body_rewrite_types; ?>">
          </label>
        </p>
        
        <p class="description"><strong id="notice-body_rewrite_types"></strong> 
          <?php _e('Define the file types you would like supported with File URL Replacement. Separate each type by a space.', ud_get_stateless_media()->domain); ?>
        </p>
      </div>

      <h4><?php _e('REST API Endpoint', ud_get_stateless_media()->domain); ?></h4>

      <div class="use_api_siteurl">
        <p>
          <select id="use_api_siteurl" name="sm[use_api_siteurl]">
            <?php if (is_network_admin()) : ?>
              <option value="" <?php selected( $sm->use_api_siteurl, '' ); ?>><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
            <?php endif; ?>
            <option value="WP_HOME" <?php selected( $sm->use_api_siteurl, 'WP_HOME' ); ?>><?php _e('WP_HOME', ud_get_stateless_media()->domain); ?></option>
            <option value="WP_SITEURL" <?php selected( $sm->use_api_siteurl, 'WP_SITEURL' ); ?>><?php _e('WP_SITEURL', ud_get_stateless_media()->domain); ?></option>
          </select>
        </p>

        <p class="description">
          <strong id="notice-use_api_siteurl"></strong> 
          <?php _e('By default, we use the <code>WP_HOME</code> endpoint for REST API requests. If you encounter problems with synchronization or data optimization functions, try using the <code>WP_SITEURL</code> option instead. This is useful if your WordPress dashboard and frontend website utilize different domain names, such as with a headless CMS configuration.', ud_get_stateless_media()->domain); ?>
        </p>
      </div>

      <h4><?php _e('Send Status Emails', ud_get_stateless_media()->domain); ?></h4>
      
      <div class="status_email_type">
        <p class="sm-status-email-type">
          <select name="sm[status_email_type]" id="sm_status_email_type">
            <?php if (is_network_admin()) : ?>
              <option value="" <?php selected( $sm->status_email_type, '' ); ?>><?php _e("Don't override"); ?></option>
            <?php endif; ?>
            <option value="false" <?php selected( $sm->status_email_type, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
            <option value="true" <?php selected( $sm->status_email_type, 'true' ); ?>><?php _e('Use Admin Email', ud_get_stateless_media()->domain); ?></option>
            <option value="custom" <?php selected( $sm->status_email_type, 'custom' ); ?>><?php _e('Use Custom Email', ud_get_stateless_media()->domain); ?></option>
          </select>  
        </p>

        <p class="description"><strong id="notice-status_email_type"></strong> 
          <?php _e('Send status emails for background synchronization and data optimization processes.', ud_get_stateless_media()->domain); ?>
        </p>
      </div>

      <div class="sm-status-email-address">
        <label for="status_email_address">
          <input name="sm[status_email_address]" type="text" id="status_status_email_addressemail" class="regular-text ltr" value="<?php echo $sm->status_email_address; ?>">
        </label>

        <p class="description">
            <?php _e('You can specify several emails, separated by comma.', ud_get_stateless_media()->domain); ?>
        </p>
      </div>

    </fieldset>
  </td>
</tr>
