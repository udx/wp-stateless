<tr>
  <th scope="row"><?php _e('File URL', ud_get_stateless_media()->domain); ?></th>

  <td>
    <fieldset class="file_url_block">
      <legend class="screen-reader-text"><span><?php _e('File URL', ud_get_stateless_media()->domain); ?></span></legend>
      
      <h4><?php _e('Preview', ud_get_stateless_media()->domain); ?></h4>
      
      <p>
        <label for="file_url_grp_preview">
          <input type="text" id="file_url_grp_preview" class="regular-text ltr" readonly="readonly">
        </label>
      </p>

      <p class="description"><?php _e('An example file url utilizing all configured settings.', ud_get_stateless_media()->domain); ?></p>
      
      <hr>
      
      <div class="form-table permalink-structure">
        <h4><?php _e('Folder', ud_get_stateless_media()->domain); ?></h4>

        <p>
          <label for="sm_bucket_folder_type">
            <select id="sm_bucket_folder_type">
              <?php if (is_network_admin()) : ?>
                <option value=""><?php _e("Don't override"); ?></option>
              <?php endif; ?>
              
              <option value="single-site"><?php _e('Single Site', ud_get_stateless_media()->domain); ?></option>
              <option value="multi-site"><?php _e('Multisite', ud_get_stateless_media()->domain); ?></option>
              <option value="custom"><?php _e('Custom', ud_get_stateless_media()->domain); ?></option>
            </select>
          </label>
        </p>

        <div class="sm-wildcards">
          <select class="select-wildcards" multiple="multiple" name="sm[root_dir][]">
            <?php foreach ($wildcards as $wildcard) : ?>
              <option <?php echo in_array($wildcard, $root_dir_values) ? 'selected="selected"' : ""; ?> <?php echo ($wildcard == '/') ? 'disabled="disabled"' : ""; ?>><?php esc_html_e($wildcard); ?></option>
              
              <?php if (in_array($wildcard, $root_dir_values) && $wildcard != '/') : ?>
                <option selected="selected" disabled="disabled">/</option>
              <?php endif; ?>
              
            <?php endforeach; ?>
          </select>
          
          <input type="text" style="display: none;" id="sm_root_dir" value="<?php echo $sm->root_dir; ?>" />
        </div>

        <p class="description">
          <strong id="notice-root_dir"></strong> 
          <?php _e('If you would like files to be uploaded into a particular folder within the bucket, define that path here.', ud_get_stateless_media()->domain); ?>
        </p>
      </div>

      <hr>

      <h4><?php _e('Domain', ud_get_stateless_media()->domain); ?></h4>

      <p>
        <label for="custom_domain">
          <input name="sm[custom_domain]" value="<?php echo $sm->custom_domain; ?>" type="text" id="custom_domain" class="regular-text ltr" placeholder="">
        </label>
      </p>

      <p class="description">
        <strong id="notice-custom_domain"></strong>
        <strong class="notice notice-is-ssl"><?php printf(__('This will require proxy/load balancer.', ud_get_stateless_media()->domain)); ?></strong>
        <?php printf(__('Replace the default GCS domain with your own custom domain. This will require you to <a href="%s" target="_blank">configure a CNAME</a>. Be advised that the bucket name and domain name must match exactly, and HTTPS is not supported with a custom domain out of the box.', ud_get_stateless_media()->domain), 'https://cloud.google.com/storage/docs/xml-api/reference-uris#cname'); ?>
      </p>

      <hr>

      <h4><?php _e('Cache-Busting', ud_get_stateless_media()->domain); ?></h4>

      <p>
        <select id="cache_busting" name="sm[hashify_file_name]">
          <?php if (is_network_admin()) : ?>
            <option value="" <?php selected( $sm->hashify_file_name, '' ); ?>><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
          <?php endif; ?>
          <option value="true" <?php selected( $sm->hashify_file_name, 'true' ); ?>><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
          <option value="false" <?php selected( $sm->hashify_file_name, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>

      <p class="description">
        <strong id="notice-hashify_file_name"></strong>
        <span id="notice-hashify_file_name-mode">
          <?php _e(sprintf("<b>Required by Stateless and Ephemeral modes. Override with the <a href='%s' target='_blank'>WP_STATELESS_MEDIA_CACHE_BUSTING</a> constant.</b>", ud_get_stateless_media()->get_docs_page_url('docs/constants/#wpstatelessmediacachebusting')), ud_get_stateless_media()->domain); ?>
        </span>

        <?php _e('Prepends a random set of numbers and letters to the filename. This is useful for preventing caching issues when uploading files that have the same filename.', ud_get_stateless_media()->domain); ?>
      </p>

      <h4><?php _e('Dynamic Image Support', ud_get_stateless_media()->domain); ?></h4>

      <p>
        <select id="dynamic_image_support" name="sm[dynamic_image_support]">
          <?php if (is_network_admin()) : ?>
            <option value="" <?php selected( $sm->dynamic_image_support, '' ); ?>><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
          <?php endif; ?>
          <option value="true" <?php selected( $sm->dynamic_image_support, 'true' ); ?>><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
          <option value="false" <?php selected( $sm->dynamic_image_support, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>

      <p class="description">
        <strong id="notice-dynamic_image_support"></strong>
        <span id="notice-dynamic_image_support-mode">
          <?php _e("<b>Not available in Stateless Mode.</b>", ud_get_stateless_media()->domain); ?>
        </span>

        <?php _e('Upload image thumbnails generated by your theme and plugins that do not register media objects with the media library. This can lead to significant negative performance impact.', ud_get_stateless_media()->domain); ?>
      </p>

      <h4><?php _e('Use Post Meta', ud_get_stateless_media()->domain); ?></h4>

      <p>
        <select id="use_postmeta" name="sm[use_postmeta]">
          <?php if (is_network_admin()) : ?>
            <option value="" <?php selected( $sm->use_postmeta, '' ); ?>><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
          <?php endif; ?>
          <option value="true" <?php selected( $sm->use_postmeta, 'true' ); ?>><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
          <option value="false" <?php selected( $sm->use_postmeta, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>

      <p class="description">
        <strong id="notice-use_postmeta"></strong>
        <?php _e('Use post meta instead of custom WP-Stateless DB tables. Enable <strong>only</strong> if you experience technical issues after upgrading to WP-Stateless 4.0.0', ud_get_stateless_media()->domain); ?>
      </p>
    </fieldset>
  </td>
</tr>
