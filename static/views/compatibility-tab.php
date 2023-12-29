<h2><?php _e("Enable or disable compatibility with other plugins.", ud_get_stateless_media()->domain); ?></h2>
    
<p><?php printf(
  __("Having an issue with another plugin? <a class='' target='_blank' href='%s' >Submit feedback</a> and let us know your issue!", ud_get_stateless_media()->domain), 
  "https://wordpress.org/support/plugin/wp-stateless/"
); ?></p>

<form method="post" action="">
  <input type="hidden" name="action" value="stateless_modules">
  <?php wp_nonce_field('wp-stateless-modules', '_smnonce'); ?>

  <table class="form-table">

    <?php foreach ($modules as $module) : ?>
      <tr>
        <th><label for="<?php echo $module->id; ?>"><?php echo $module->title; ?></label></th>

        <td>
          <?php
          $name = sprintf('stateless-modules[%s]', $module->id);

          $disabled = $module->is_constant || ($module->is_network_override || !$module->is_plugin_active || !$module->is_mode_supported) && !is_network_admin(); 
          $disabled = $disabled ? 'disabled="true"' : '';
          ?>

          <select name="<?php echo $name; ?>" id="<?php echo $module->id; ?>" value="<?php echo $module->enabled; ?>" <?php echo $disabled; ?>>
            <?php if (is_network_admin()) : ?>
              <option value="" <?php selected( $module->enabled, '' ); ?>><?php _e("Don't override", ud_get_stateless_media()->domain); ?></option>
            <?php endif; ?>

            <?php if ( $module->enabled == 'inactive' ) : ?>
              <option value="inactive" <?php selected( $module->enabled, 'inactive' ); ?>><?php _e('Not Available', ud_get_stateless_media()->domain); ?></option>
            <?php endif; ?>

            <option value="false" <?php selected( $module->enabled, 'false' ); ?>><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
            <option value="true" <?php selected( $module->enabled, 'true' ); ?>><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
          </select>

          <p class="description">
            <?php if ( !$module->is_plugin_active && $module->is_plugin && $module->is_mode_supported ) : ?>
              <strong><?php _e("Please activate the plugin first.", ud_get_stateless_media()->domain); ?></strong>
            <?php endif; ?>

            <?php if ( !$module->is_plugin_active && $module->is_theme && $module->is_mode_supported ) : ?>
              <strong><?php _e("Please activate the theme first.", ud_get_stateless_media()->domain); ?></strong>
            <?php endif; ?>

            <?php if ( !$module->is_mode_supported ) : ?>
              <strong><?php printf( __("This compatibility does not support %s mode.", ud_get_stateless_media()->domain), $module->mode ); ?></strong>
            <?php endif; ?>

            <?php if ( $module->is_constant ) : ?>
              <strong><?php _e("Currently configured via a constant.", ud_get_stateless_media()->domain); ?></strong>
            <?php endif; ?>

            <?php if ( $module->is_network_override ) : ?>
              <strong><?php _e("Currently configured via network settings.", ud_get_stateless_media()->domain); ?></strong>
            <?php endif; ?>

            <span><?php echo $module->description?></span>
          </p>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php submit_button(null, 'primary', 'submit', true, array('id' => 'save-compatibility')); ?>
</form>

