<tr>
  <th scope="row"><?php _e('Settings Panel Visibility', ud_get_stateless_media()->domain); ?></th>

  <td>
    <fieldset>
      <legend class="screen-reader-text"><span><?php _e('Settings Panel Visibility', ud_get_stateless_media()->domain); ?></span></legend>

      <p>
        <select name="sm[hide_settings_panel]" id="hide_settings_panel" <?php disabled( array_key_exists('hide_settings_panel', $sm->readonly) ); ?>>
          <option value="false" <?php selected( $sm->hide_settings_panel, 'false' ); ?>><?php _e('Visible', ud_get_stateless_media()->domain); ?></option>
          <option value="true" <?php selected( $sm->hide_settings_panel, 'true' ); ?>><?php _e('Hidden', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>
      
      <p class="description"><?php _e("Control the visibility and access of the WP-Stateless settings panel within individual network sites."); ?></p>
    </fieldset>
  </td>
</tr>

<tr>
  <th scope="row"><?php _e('Setup Assistant Visibility', ud_get_stateless_media()->domain); ?></th>
  
  <td>
    <fieldset>
      <legend class="screen-reader-text"><span><?php _e('Setup Assistant Visibility', ud_get_stateless_media()->domain); ?></span></legend>
      
      <p>
        <select name="sm[hide_setup_assistant]" id="hide_setup_assistant" <?php disabled( array_key_exists('hide_setup_assistant', $sm->readonly) ); ?>>
          <option value="false" <?php selected( $sm->hide_setup_assistant, 'false' ); ?>><?php _e('Visible', ud_get_stateless_media()->domain); ?></option>
          <option value="true" <?php selected( $sm->hide_setup_assistant, 'true' ); ?>><?php _e('Hidden', ud_get_stateless_media()->domain); ?></option>
        </select>
      </p>
      
      <p class="description"><?php _e("Control the visibility and access of the WP-Stateless setup assistant within individual network sites."); ?></p>
    </fieldset>
  </td>
</tr>
