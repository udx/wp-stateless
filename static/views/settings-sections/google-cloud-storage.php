<tr>
  <th scope="row"><?php _e('Google Cloud Storage (GCS)', ud_get_stateless_media()->domain); ?></th>

  <td>
    <fieldset>
      <legend class="screen-reader-text"><span><?php _e('Google Cloud Storage (GCS)', ud_get_stateless_media()->domain); ?></span></legend>
            
      <h4><?php _e('Bucket', ud_get_stateless_media()->domain); ?></h4>
      
      <p>
        <label for="bucket_name">
          <input name="sm[bucket]" type="text" id="bucket_name" class="regular-text ltr" value="<?php echo $sm->bucket?>">
        </label>
      </p>

      <p class="description">
        <strong id="notice-bucket"></strong> 
        <?php _e('The name of the GCS bucket.', ud_get_stateless_media()->domain); ?>
      </p>
        
      <hr>

      <h4><?php _e('Service Account JSON', ud_get_stateless_media()->domain); ?></h4>
          
      <p>
        <label for="service_account_json">
          <textarea name="sm[key_json]" 
            type="text" 
            id="service_account_json" 
            class="regular-text ltr" 
            autocomplete="off" 
            autocorrect="off" 
            autocapitalize="off" 
            spellcheck="false"
          ><?php echo $sm->key_json; ?></textarea>
        </label>
      </p>
        
      <p class="description">
        <strong id="notice-key_json"></strong> 
        <?php _e('Private key in JSON format for the service account WP-Stateless will use to connect to your Google Cloud project and bucket. Empty this field to access the Stateless Setup Assistant.', ud_get_stateless_media()->domain); ?>
      </p>
        
      <hr>

      <h4><?php _e('Cache-Control', ud_get_stateless_media()->domain); ?></h4>
        
      <p>
        <label for="gcs_cache_control_text">
          <input name="sm[cache_control]" type="text" id="gcs_cache_control_text" class="regular-text ltr" placeholder="<?php echo ud_get_stateless_media()->get_default_cache_control(); ?>" value="<?php echo $sm->cache_control; ?>">
        </label>
      </p>

      <p class="description">
        <strong id="notice-cache_control"></strong> 
        <?php _e('Override the default cache control assigned by GCS.', ud_get_stateless_media()->domain); ?>
      </p>
      
      <hr>

      <h4><?php _e('Delete GCS File', ud_get_stateless_media()->domain); ?></h4>

      <p>
        <select name="sm[delete_remote]" id="gcs_delete_file">
          <?php if (is_network_admin()) : ?>
            <option value="" <?php selected( $sm->delete_remote, '' ); ?>><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
          <?php endif; ?>
          <option value="true" <?php selected( $sm->delete_remote, 'true' ); ?>><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
          <option value="false" <?php selected( $sm->delete_remote, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>

      <p class="description">
        <strong id="notice-delete_remote"></strong> 
        <?php _e('Delete the GCS file when the file is deleted from WordPress.', ud_get_stateless_media()->domain); ?>
      </p>
        
    </fieldset>
  </td>
</tr>
