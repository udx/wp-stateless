<div class="wrap" ng-app="wpStatelessApp">
  <div id="stateless-settings-page-title">
    <h1><?php _e('WP-Stateless', ud_get_stateless_media()->domain); ?></h1>
    <div class="description"><?php _e('Upload and serve your WordPress media files from Google Cloud Storage.', ud_get_stateless_media()->domain); ?></div>
    <div class="no-js-error" ng-hide="jsLoaded" ng-controller="noJSWarning">You have a problem with JS.</div>
  </div>
  <h2 class="nav-tab-wrapper">
    <a href="#stless_settings_tab" class="stless_setting_tab nav-tab  nav-tab-active"><?php _e('Settings', ud_get_stateless_media()->domain); ?></a>
    <?php if (!is_network_admin() && !isset($_SERVER["GAE_VERSION"]) && ud_get_stateless_media('sm.mode') != 'disabled') : ?>
      <a href="#stless_sync_tab" class="stless_setting_tab nav-tab"><?php _e('Sync', ud_get_stateless_media()->domain); ?></a>
    <?php endif; ?>
    <a href="#stless_compatibility_tab" class="stless_setting_tab nav-tab"><?php _e('Compatibility', ud_get_stateless_media()->domain); ?></a>
    <a href="#stless_questions_tab" class="stless_setting_tab nav-tab"><?php _e('Support', ud_get_stateless_media()->domain); ?></a>
  </h2>

  <div class="stless_settings">
    <div id="stless_settings_tab" class="stless_settings_content active" ng-controller="wpStatelessSettings">
      <form method="post" action="">
        <input type="hidden" name="action" value="stateless_settings">
        <?php wp_nonce_field('wp-stateless-settings', '_smnonce'); ?>
        <table class="form-table">
          <tbody>
            <?php if (is_network_admin()) : ?>
              <tr>
                <th scope="row"><?php _e('Settings Panel Visibility', ud_get_stateless_media()->domain); ?></th>
                <td>
                  <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Settings Panel Visibility', ud_get_stateless_media()->domain); ?></span></legend>
                    <p>
                      <select name="sm[hide_settings_panel]" id="hide_settings_panel" ng-model="sm.hide_settings_panel" ng-disabled="sm.readonly.hide_settings_panel">
                        <option value="false"><?php _e('Visible', ud_get_stateless_media()->domain); ?></option>
                        <option value="true"><?php _e('Hidden', ud_get_stateless_media()->domain); ?></option>
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
                      <select name="sm[hide_setup_assistant]" id="hide_setup_assistant" ng-model="sm.hide_setup_assistant" ng-disabled="sm.readonly.hide_setup_assistant">
                        <option value="false"><?php _e('Visible', ud_get_stateless_media()->domain); ?></option>
                        <option value="true"><?php _e('Hidden', ud_get_stateless_media()->domain); ?></option>
                      </select>
                    </p>
                    <p class="description"><?php _e("Control the visibility and access of the WP-Stateless setup assistant within individual network sites."); ?></p>
                  </fieldset>
                </td>
              </tr>
            <?php endif; ?>
            <tr>
              <th scope="row"><?php _e('General', ud_get_stateless_media()->domain); ?></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><span><?php _e('General', ud_get_stateless_media()->domain); ?></span></legend>
                  <h4><?php _e('Mode', ud_get_stateless_media()->domain); ?></h4>
                  <p class="description"><strong ng-bind="sm.showNotice('mode')"></strong></p>
                  <?php if (is_network_admin()) : ?>
                    <p class="sm-mode">
                      <label for="sm_mode_not_override"><input ng-model="sm.mode" id="sm_mode_not_override" type="radio" name="sm[mode]" value="" ng-checked="sm.mode == ''" ng-disabled="sm.readonly.mode"><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Don\'t override.', ud_get_stateless_media()->domain); ?></small></label>
                    </p>
                  <?php endif; ?>
                  <p class="sm-mode">
                    <label for="sm_mode_disabled"><input ng-model="sm.mode" id="sm_mode_disabled" type="radio" name="sm[mode]" value="disabled" ng-checked="sm.mode == 'disabled'" ng-disabled="sm.readonly.mode"><?php _e('Disabled', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Disable Stateless Media.', ud_get_stateless_media()->domain); ?></small></label>
                  </p>
                  <p class="sm-mode">
                    <label for="sm_mode_backup"><input ng-model="sm.mode" id="sm_mode_backup" type="radio" name="sm[mode]" value="backup" ng-checked="sm.mode == 'backup'" ng-disabled="sm.readonly.mode"><?php _e('Backup', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Upload media files to Google Storage and serve local file urls.', ud_get_stateless_media()->domain); ?></small></label>
                  </p>
                  <p class="sm-mode">
                    <label for="sm_mode_cdn"><input ng-model="sm.mode" id="sm_mode_cdn" type="radio" name="sm[mode]" value="cdn" ng-checked="sm.mode == 'cdn'" ng-disabled="sm.readonly.mode"><?php _e('CDN', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Copy media files to Google Storage and serve them directly from there.', ud_get_stateless_media()->domain); ?></small></label>
                  </p>
                  <p class="sm-mode">
                    <label for="sm_mode_ephemeral"><input ng-model="sm.mode" id="sm_mode_ephemeral" type="radio" name="sm[mode]" value="ephemeral" ng-checked="sm.mode == 'ephemeral'" ng-disabled="sm.readonly.mode"><?php _e('Ephemeral', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Store and serve media files with Google Cloud Storage only. Media files are not stored locally, but local storage is used temporarily for processing and is required for certain compatibilities, generating thumbnails for PDF documents.', ud_get_stateless_media()->domain); ?></small></label>
                  </p>
                  <p class="sm-mode">
                    <label for="sm_mode_stateless"><input ng-model="sm.mode" id="sm_mode_stateless" type="radio" name="sm[mode]" value="stateless" ng-checked="sm.mode == 'stateless'" ng-disabled="sm.readonly.mode"><?php _e('Stateless', ud_get_stateless_media()->domain); ?><small class="description"><?php _e('Store and serve media files with Google Cloud Storage only. Media files are not stored locally.', ud_get_stateless_media()->domain); ?></small></label>
                  </p>
                  <hr>

                  <h4><?php _e('File URL Replacement', ud_get_stateless_media()->domain); ?></h4>
                  <p class="sm-file-url">
                    <select name="sm[body_rewrite]" id="sm_body_rewrite" ng-model="sm.body_rewrite" ng-disabled="sm.readonly.body_rewrite">
                      <?php if (is_network_admin()) : ?>
                        <option value=""><?php _e("Don't override"); ?></option>
                      <?php endif; ?>
                      <option value="false"><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
                      <option value="enable_editor"><?php _e('Enable Editor', ud_get_stateless_media()->domain); ?></option>
                      <option value="enable_meta"><?php _e('Enable Meta', ud_get_stateless_media()->domain); ?></option>
                      <option value="true"><?php _e('Enable Editor & Meta', ud_get_stateless_media()->domain); ?></option>
                    </select>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('body_rewrite')"></strong> <?php _e('Scans post content and meta during presentation and replaces local media file urls with GCS urls. When selecting meta or true depending on the amount of meta, this could be significantly impact performance negatively. This setting does not modify your database.', ud_get_stateless_media()->domain); ?></p>

                  <h4 ng-show="sm.body_rewrite == 'true' || sm.body_rewrite == 'enable_editor' || sm.body_rewrite == 'enable_meta'"><?php _e('Supported File Types', ud_get_stateless_media()->domain); ?></h4>
                  <div ng-show="sm.body_rewrite == 'true' || sm.body_rewrite == 'enable_editor' || sm.body_rewrite == 'enable_meta'" class="body_rewrite_types">
                    <p>
                      <label for="body_rewrite_types">
                        <input name="sm[body_rewrite_types]" type="text" id="body_rewrite_types" class="regular-text ltr" ng-model="sm.body_rewrite_types" ng-disabled="sm.readonly.body_rewrite_types">
                      </label>
                    </p>
                    <p class="description"><strong ng-bind="sm.showNotice('body_rewrite_types')"></strong> <?php _e('Define the file types you would like supported with File URL Replacement. Separate each type by a space.', ud_get_stateless_media()->domain); ?></p>
                  </div>
                </fieldset>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Google Cloud Storage (GCS)', ud_get_stateless_media()->domain); ?></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><span><?php _e('Google Cloud Storage (GCS)', ud_get_stateless_media()->domain); ?></span></legend>
                  <h4><?php _e('Bucket', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <label for="bucket_name">
                      <input name="sm[bucket]" type="text" id="bucket_name" class="regular-text ltr" ng-model="sm.bucket" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.bucket">
                    </label>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('bucket')"></strong> <?php _e('The name of the GCS bucket.', ud_get_stateless_media()->domain); ?></p>
                  <hr>

                  <h4><?php _e('Service Account JSON', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <label for="service_account_json">
                      <textarea name="sm[key_json]" type="text" id="service_account_json" class="regular-text ltr" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ng-disabled="sm.readonly.key_json">{{sm.key_json}}</textarea>
                    </label>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('key_json')"></strong> <?php _e('Private key in JSON format for the service account WP-Stateless will use to connect to your Google Cloud project and bucket. Empty this field to access the Stateless Setup Assistant.', ud_get_stateless_media()->domain); ?></p>
                  <hr>

                  <h4><?php _e('Cache-Control', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <label for="gcs_cache_control_text">
                      <input name="sm[cache_control]" type="text" id="gcs_cache_control_text" class="regular-text ltr" placeholder="public, max-age=36000, must-revalidate" ng-model="sm.cache_control" ng-disabled="sm.readonly.cache_control">
                    </label>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('cache_control')"></strong> <?php _e('Override the default cache control assigned by GCS.', ud_get_stateless_media()->domain); ?></p>
                  <hr>

                  <h4><?php _e('Delete GCS File', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <select name="sm[delete_remote]" id="gcs_delete_file" ng-model="sm.delete_remote" ng-disabled="sm.readonly.delete_remote">
                      <?php if (is_network_admin()) : ?>
                        <option value=""><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
                      <?php endif; ?>
                      <option value="true"><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
                      <option value="false"><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
                    </select>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('delete_remote')"></strong> <?php _e('Delete the GCS file when the file is deleted from WordPress.', ud_get_stateless_media()->domain); ?></p>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('File URL', ud_get_stateless_media()->domain); ?></th>
              <td>
                <fieldset class="file_url_block">
                  <legend class="screen-reader-text"><span><?php _e('File URL', ud_get_stateless_media()->domain); ?></span></legend>
                  <h4><?php _e('Preview', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <label for="file_url_grp_preview">
                      <input type="text" id="file_url_grp_preview" class="regular-text ltr" readonly="readonly" ng-model="sm.preview_url" ng-disabled="sm.readonly">
                    </label>
                  </p>
                  <p class="description"><?php _e('An example file url utilizing all configured settings.', ud_get_stateless_media()->domain); ?></p>
                  <hr>
                  <div class="form-table permalink-structure">
                    <h4><?php _e('Folder', ud_get_stateless_media()->domain); ?></h4>
                    <p>
                      <label for="bucket_folder_name">
                        <select id="sm_bucket_folder_type" ng-model="sm.bucket_folder_type" ng-disabled="sm.readonly.root_dir">
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
                      <select class=" select-wildcards" multiple="multiple" name="sm[root_dir][]" ng-disabled="sm.readonly.root_dir">
                        <?php foreach ($wildcards as $wildcard => $replace) : ?>
                          <option <?php echo in_array($wildcard, $root_dir_values) ? 'selected="selected"' : ""; ?> <?php echo ($wildcard == '/') ? 'disabled="disabled"' : ""; ?>><?php echo $wildcard; ?></option>
                          <?php if (in_array($wildcard, $root_dir_values) && $wildcard != '/') : ?>
                            <option selected="selected" disabled="disabled">/</option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                      <input type="text" style="display: none;" id="sm_root_dir" ng-model="sm.root_dir" ng-change="sm.generatePreviewUrl()" />
                    </div>

                    <p class="description"><strong ng-bind="sm.showNotice('root_dir')"></strong> <?php _e('If you would like files to be uploaded into a particular folder within the bucket, define that path here.', ud_get_stateless_media()->domain); ?></p>
                  </div>

                  <hr>

                  <h4><?php _e('Domain', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <label for="bucket_folder_name">
                      <input name="sm[custom_domain]" ng-model="sm.custom_domain" type="text" id="bucket_folder_name" class="regular-text ltr" placeholder="" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.custom_domain">
                    </label>
                  </p>
                  <p class="description">
                    <strong ng-bind="sm.showNotice('custom_domain')"></strong>
                    <strong class="notice" ng-show="sm.is_ssl"><?php printf(__('This will require proxy/load balancer.', ud_get_stateless_media()->domain)); ?></strong>
                    <?php printf(__('Replace the default GCS domain with your own custom domain. This will require you to <a href="%s" target="_blank">configure a CNAME</a>. Be advised that the bucket name and domain name must match exactly, and HTTPS is not supported with a custom domain out of the box.', ud_get_stateless_media()->domain), 'https://cloud.google.com/storage/docs/xml-api/reference-uris#cname'); ?>
                  </p>
                  <hr>

                  <h4><?php _e('Cache-Busting', ud_get_stateless_media()->domain); ?></h4>
                  <p>
                    <select id="cache_busting" name="sm[hashify_file_name]" ng-model="sm.hashify_file_name" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.hashify_file_name || sm.mode == 'stateless'">
                      <?php if (is_network_admin()) : ?>
                        <option value=""><?php _e('Don\'t override', ud_get_stateless_media()->domain); ?></option>
                      <?php endif; ?>
                      <option value="true"><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
                      <option value="false"><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
                    </select>
                  </p>
                  <p class="description"><strong ng-bind="sm.showNotice('hashify_file_name')"></strong>
                    <span ng-show="sm.mode == 'stateless' && sm.readonly.hashify_file_name != 'constant'">
                      <?php _e(sprintf("<b>Required by Stateless Mode. Override with the <a href='%s' target='_blank'>WP_STATELESS_MEDIA_CACHE_BUSTING</a> constant.</b>", "https://wp-stateless.github.io/docs/constants/#wp_stateless_media_cache_busting"), ud_get_stateless_media()->domain); ?>
                    </span>
                    <?php _e('Prepends a random set of numbers and letters to the filename. This is useful for preventing caching issues when uploading files that have the same filename.', ud_get_stateless_media()->domain); ?></p>
                </fieldset>
              </td>
            </tr>
          </tbody>
        </table>

        <?php submit_button(null, 'primary', 'submit', true, array('id' => 'save-settings')); ?>
      </form>
    </div>
    <?php if (!is_network_admin() && !isset($_SERVER["GAE_VERSION"]) && ud_get_stateless_media('sm.mode') != 'disabled') : ?>
      <div id="stless_sync_tab" class="stless_settings_content">
        <?php include 'processing_interface.php'; ?>
      </div>
    <?php endif; ?>
    <div id="stless_compatibility_tab" class="stless_settings_content" ng-controller="wpStatelessCompatibility">
      <div class="container-fluid">
        <h2><?php _e("Enable or disable compatibility with other plugins."); ?></h2>
        <p><?php _e(sprintf("Having an issue with another plugin? <a class='stless_setting_tab' href='%s'>Submit feedback</a> and let us know your issue!", "#stless_questions_tab")); ?></p>
        <form method="post" action="">
          <input type="hidden" name="action" value="stateless_modules">
          <?php wp_nonce_field('wp-stateless-modules', '_smnonce'); ?>

          <table class="form-table">
            <tr ng-repeat="module in modules">
              <th>
                <label for="{{module.id}}">{{module.title}}</label>
              </th>
              <td>
                <select name="stateless-modules[{{module.id}}]" id="{{module.id}}" ng-model="module.enabled" ng-disabled="module.is_constant || (module.is_network_override ||!module.is_plugin_active ||!module.is_mode_supported) && !module.is_network_admin">
                  <?php if (is_network_admin()) : ?>
                    <option value=""><?php _e("Don't override"); ?></option>
                  <?php endif; ?>
                  <option value="inactive" ng-show="module.enabled == 'inactive'"><?php _e('Not Available', ud_get_stateless_media()->domain); ?></option>
                  <option value="false"><?php _e('Disable', ud_get_stateless_media()->domain); ?></option>
                  <option value="true"><?php _e('Enable', ud_get_stateless_media()->domain); ?></option>
                </select>
                <p class="description">
                  <strong ng-show="!module.is_plugin_active && module.is_plugin && module.is_mode_supported"><?php _e("Please activate the plugin first."); ?></strong>
                  <strong ng-show="!module.is_plugin_active && module.is_theme && module.is_mode_supported"><?php _e("Please activate the theme first."); ?></strong>
                  <strong ng-show="!module.is_mode_supported"><?php _e("This compatibility does not support {{module.mode}} mode."); ?></strong>
                  <strong ng-show="module.is_constant"><?php _e("Currently configured via a constant."); ?></strong>
                  <strong ng-show="module.is_network_override"><?php _e("Currently configured via network settings."); ?></strong>
                  <span ng-bind-html="module.description"></span>
                </p>
              </td>
            </tr>
          </table>
          <?php submit_button(null, 'primary', 'submit', true, array('id' => 'save-compatibility')); ?>
        </form>
      </div>
    </div>
    <div id="stless_questions_tab" class="stless_settings_content">
      <!--[if lte IE 8]>
            <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2-legacy.js"></script>
            <![endif]-->
      <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
      <script>
        hbspt.forms.create({
          portalId: '3453418',
          formId: 'cad1f6e1-7825-4e6d-a3e7-278c91abce7e',
          submitButtonClass: 'button button-primary',
        });
      </script>
    </div>
  </div>

</div>