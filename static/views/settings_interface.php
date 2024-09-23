<div class="wrap">
  <div id="stateless-settings-page-title">
    <h1>
      <?php _e('WP-Stateless', ud_get_stateless_media()->domain); ?> 
      <a href="<?php echo ud_get_stateless_media()->get_docs_page_url(); ?>" title="<?php _e('Documentation', ud_get_stateless_media()->domain); ?>" target="_blank" class="stateless-settings-docs-link"><span class="dashicons dashicons-editor-help"></span></a>
    </h1>
    
    <div class="description"><?php _e('Upload and serve your WordPress media files from Google Cloud Storage.', ud_get_stateless_media()->domain); ?></div>
  </div>
  <h2 class="nav-tab-wrapper">
    <a href="#stless_settings_tab" class="stless_setting_tab nav-tab <?php if ($tab == 'stless_settings_tab') echo 'nav-tab-active'; ?>"><?php _e('Settings', ud_get_stateless_media()->domain); ?></a>
    <?php if (!is_network_admin() && !apply_filters('wp_stateless_is_app_engine', false) && ud_get_stateless_media('sm.mode') != 'disabled') : ?>
      <a href="#stless_sync_tab" class="stless_setting_tab nav-tab <?php if ($tab == 'stless_sync_tab') echo 'nav-tab-active'; ?>"><?php _e('Sync', ud_get_stateless_media()->domain); ?></a>
    <?php endif; ?>
    <a href="#stless_compatibility_tab" class="stless_setting_tab nav-tab <?php if ($tab == 'stless_compatibility_tab') echo 'nav-tab-active'; ?>"><?php _e('Compatibility', ud_get_stateless_media()->domain); ?></a>
    <?php if ( apply_filters('wp_stateless_addons_tab_visible', false) ) : ?>
      <a href="#stless_addons_tab" class="stless_setting_tab nav-tab <?php if ($tab == 'stless_addons_tab') echo 'nav-tab-active'; ?>"><?php _e('Addons', ud_get_stateless_media()->domain); ?></a>
    <?php endif; ?>
    <?php if ( apply_filters('wp_stateless_status_tab_visible', false) ) : ?>
      <a href="#stless_status_tab" class="stless_setting_tab stless_status_tab nav-tab <?php if ($tab == 'stless_status_tab') echo 'nav-tab-active'; ?>"><?php _e('Status', ud_get_stateless_media()->domain); ?></a>
    <?php endif; ?>
   </h2>

  <div class="stless_settings">
    <div id="stless_settings_tab" class="stless_settings_content <?php if ($tab == 'stless_settings_tab') echo 'active'; ?>">
      <?php do_action('wp_stateless_settings_tab_content'); ?>
    </div>

    <?php if (!is_network_admin() && !apply_filters('wp_stateless_is_app_engine', false) && ud_get_stateless_media('sm.mode') != 'disabled') : ?>
      <div id="stless_sync_tab" class="stless_settings_content <?php if ($tab == 'stless_sync_tab') echo 'active'; ?>">
        <?php do_action('wp_stateless_processing_tab_content'); ?>
      </div>
    <?php endif; ?>

    <?php if ( apply_filters('wp_stateless_compatibility_tab_visible', false) ) : ?>
      <div id="stless_compatibility_tab" class="stless_settings_content <?php if ($tab == 'stless_compatibility_tab') echo 'active'; ?>">
        <div class="container-fluid">
          <?php do_action('wp_stateless_compatibility_tab_content'); ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ( apply_filters('wp_stateless_addons_tab_visible', false) ) : ?>
      <div id="stless_addons_tab" class="stless_settings_content <?php if ($tab == 'stless_addons_tab') echo 'active'; ?>">
        <div class="container-fluid">
          <?php do_action('wp_stateless_addons_tab_content'); ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ( apply_filters('wp_stateless_status_tab_visible', false) ) : ?>
      <div id="stless_status_tab" class="stless_settings_content <?php if ($tab == 'stless_status_tab') echo 'active'; ?>">
        <?php do_action('wp_stateless_status_tab_content'); ?>
      </div>
    <?php endif; ?>
  </div>
</div>
