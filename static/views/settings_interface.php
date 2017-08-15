<div class="wrap" ng-app="wpStatelessApp">
    <div id="stateless-settings-page-title">
        <h1>WP-Stateless</h1>
        <div class="description">Upload and serve your WordPress media files from Google Cloud Storage.</div>
    </div>
    <h2 class="nav-tab-wrapper">  
        <a href="#stless_settings_tab" class="stless_setting_tab nav-tab  nav-tab-active">Settings</a>  
        <?php if(!is_network_admin()): ?>
        <a href="#stless_sync_tab" class="stless_setting_tab nav-tab">Sync</a>  
        <?php endif; ?>    
        <a href="#stless_questions_tab" class="stless_setting_tab nav-tab">Questions</a>  
    </h2>  

    <div class="stless_settings">
        <div id="stless_settings_tab" class="stless_settings_content active" ng-controller="wpStatelessSettings">
            <form method="post" action=""> 
                <input type="hidden" name="action" value="stateless_settings">
                <?php wp_nonce_field('wp-stateless-settings', '_smnonce');?>
                <table class="form-table">
                    <tbody>
                    <?php if(is_network_admin()): ?>
                        <tr>
                            <th scope="row">Settings Panel Visibility</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Settings Panel Visibility</span></legend>
                                    <p>
                                        <select name="sm[hide_settings_panel]" id="hide_settings_panel" ng-model="sm.hide_settings_panel" ng-disabled="sm.readonly.hide_settings_panel">
                                            <option value="false">Visible</option>
                                            <option value="true">Hidden</option>
                                        </select>
                                    </p>
                                    <p class="description">Control the visibility and access of the WP-Stateless settings panel within individual network sites.</p>                  
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Setup Assistant Visibility</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Setup Assistant Visibility</span></legend>
                                    <p>
                                        <select name="sm[hide_setup_assistant]" id="hide_setup_assistant" ng-model="sm.hide_setup_assistant" ng-disabled="sm.readonly.hide_setup_assistant">
                                            <option value="false">Visible</option>
                                            <option value="true">Hidden</option>
                                        </select>
                                    </p>
                                    <p class="description">Control the visibility and access of the WP-Stateless setup assistant within individual network sites.</p>                  
                                </fieldset>
                            </td>
                        </tr>
                    <?php endif; ?>    
                        <tr>
                            <th scope="row">General</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>General</span></legend>
                                    <h4>Mode</h4>
                                    <?php if(is_network_admin()): ?>
                                    <p class="sm-mode">
                                        <label for="sm_mode_disabled"><input id="sm_mode_not_override" type="radio" name="sm[mode]" value="" ng-checked="sm.mode == ''" ng-disabled="sm.readonly.mode">Don't override<small class="description">Don't override.</small></label>
                                    </p>
                                    <?php endif; ?>
                                    <p class="sm-mode">
                                        <label for="sm_mode_disabled"><input id="sm_mode_disabled" type="radio" name="sm[mode]" value="disabled" ng-checked="sm.mode == 'disabled'" ng-disabled="sm.readonly.mode">Disabled<small class="description">Disable Stateless Media.</small></label>
                                    </p>
                                    <p class="sm-mode">
                                        <label for="sm_mode_backup"><input id="sm_mode_backup" type="radio" name="sm[mode]" value="backup" ng-checked="sm.mode == 'backup'" ng-disabled="sm.readonly.mode">Backup<small class="description">Upload media files to Google Storage and serve local file urls.</small></label>
                                    </p>
                                    <p class="sm-mode">
                                        <label for="sm_mode_cdn"><input id="sm_mode_cdn" type="radio" name="sm[mode]" value="cdn" ng-checked="sm.mode == 'cdn'"  ng-disabled="sm.readonly.mode">CDN<small class="description">Copy media files to Google Storage and serve them directly from there.</small></label>
                                    </p>
                                    <p class="sm-mode">
                                        <label for="sm_mode_stateless"><input id="sm_mode_stateless" type="radio" name="sm[mode]" value="stateless" ng-checked="sm.mode == 'stateless'" ng-disabled="sm.readonly.mode">Stateless<small class="description">Store and serve media files with Google Cloud Storage only. Media files are not stored locally.</small></label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('mode')" ></strong></p>
                                    <hr>

                                    <h4>File URL Replacement</h4>
                                    <p class="sm-file-url">
                                        <select name="sm[body_rewrite]" id="sm_file_url" ng-model="sm.body_rewrite" ng-disabled="sm.readonly.body_rewrite">
                                            <?php if(is_network_admin()): ?>
                                            <option value="">Don't override</option>
                                            <?php endif; ?>
                                            <option value="true">Enable</option>
                                            <option value="false">Disable</option>
                                        </select>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('body_rewrite')" ></strong> Scans post content and meta during presentation and replaces local media file urls with GCS urls. This setting does not modify your database.</p>
                                    <hr>

                                    <h4>Dynamic Image Support</h4>
                                    <p class="dynamic_img_sprt">
                                        <select name="sm[on_fly]" id="dynamic_img_sprt" ng-model="sm.on_fly" ng-disabled="sm.readonly.on_fly">
                                            <?php if(is_network_admin()): ?>
                                            <option value="">Don't override</option>
                                            <?php endif; ?>
                                            <option value="true">Enable</option>
                                            <option value="false">Disable</option>
                                        </select>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('on_fly')" ></strong> Upload image thumbnails generated by your theme and plugins that do not register media objects with the media library.</p>                  
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Google Cloud Storage (GCS)</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Google Cloud Storage (GCS)</span></legend>
                                    <h4>Bucket</h4>
                                    <p>
                                        <label for="bucket_name">
                                            <input name="sm[bucket]" type="text" id="bucket_name" class="regular-text ltr" ng-model="sm.bucket" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.bucket">
                                        </label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('bucket')" ></strong> The name of the GCS bucket.</p>
                                    <hr>       

                                    <h4>Bucket Folder</h4>
                                    <p>
                                        <label for="bucket_folder_name">
                                            <input name="sm[root_dir]" type="text" id="bucket_folder_name" class="regular-text ltr" ng-model="sm.root_dir" ng-disabled="sm.readonly.root_dir">
                                        </label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('root_dir')" ></strong> If you would like files to be uploaded into a particular folder within the bucket, define that path here.</p>
                                    <hr>

                                    <h4>Service Account JSON</h4>
                                    <p>
                                        <label for="service_account_json">
                                            <textarea name="sm[key_json]" type="text" id="service_account_json" class="regular-text ltr" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ng-disabled="sm.readonly.key_json">{{sm.key_json}}</textarea>
                                        </label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('key_json')" ></strong> Private key in JSON format for the service account WP-Stateless will use to connect to your Google Cloud project and bucket.</p>
                                    <hr>

                                    <h4>Cache-Control</h4>
                                    <p>
                                        <label for="gcs_cache_control_text">
                                            <input name="sm[cache_control]" type="text" id="gcs_cache_control_text" class="regular-text ltr" placeholder="public, max-age=36000, must-revalidate" ng-model="sm.cache_control" ng-disabled="sm.readonly.cache_control">
                                        </label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('cache_control')" ></strong> Override the default cache control assigned by GCS.</p>
                                    <hr>

                                    <h4>Delete GCS File</h4>
                                    <p>
                                        <select name="sm[delete_remote]" id="gcs_delete_file" ng-model="sm.delete_remote" ng-disabled="sm.readonly.delete_remote">
                                            <?php if(is_network_admin()): ?>
                                            <option value="">Don't override</option>
                                            <?php endif; ?>
                                            <option value="true">Enable</option>
                                            <option value="false">Disable</option>
                                        </select>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('on_fly')" ></strong> Delete the GCS file when the file is deleted from WordPress.</p>
                                </fieldset>
                            </td>
                        </tr>  
                        <tr>
                            <th scope="row">File URL</th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>File URL</span></legend>
                                    <h4>Preview</h4>
                                    <p>
                                        <label for="file_url_grp_preview">
                                            <input type="text" id="file_url_grp_preview" class="regular-text ltr" readonly="readonly" ng-model="sm.preview_url" ng-disabled="sm.readonly">
                                        </label>
                                    </p>
                                    <p class="description">An example file url utilizing all configured settings.</p>
                                    <hr>        

                                    <h4>Domain</h4>
                                    <p>
                                        <label for="bucket_folder_name">
                                            <input name="sm[custom_domain]" ng-model="sm.custom_domain" type="text" id="bucket_folder_name" class="regular-text ltr" placeholder="storage.googleapis.com" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.custom_domain">
                                        </label>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('custom_domain')" ></strong> Replace the default GCS domain with your own custom domain. This will require you to <a href="https://cloud.google.com/storage/docs/xml-api/reference-uris#cname" target="_blank">configure a CNAME</a>. Be advised that the bucket name and domain name must match exactly and HTTPS is not supported with a custom domain.</p>
                                    <hr>

                                    <?php if(!is_network_admin()): ?>
                                    <h4>Organization</h4>
                                    <p>
                                        <select id="org_url_grp" name="sm[organize_media]" ng-model="sm.organize_media" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.organize_media">
                                            <?php if(is_network_admin()): ?>
                                            <option value="">Don't override</option>
                                            <?php endif; ?>
                                            <option value="1">Enable</option>
                                            <option value="">Disable</option>
                                        </select>
                                    </p>
                                    <p class="description">Organize uploads into year and month based folders. This will update the <a href="<?php echo admin_url("options-media.php"); ?>">related WordPress media setting</a>.</p>
                                    <?php endif; ?>    

                                    <hr>
                                    <h4>Cache-Busting</h4>
                                    <p>
                                        <select id="cache_busting" name="sm[hashify_file_name]" ng-model="sm.hashify_file_name" ng-change="sm.generatePreviewUrl()" ng-disabled="sm.readonly.hashify_file_name">
                                            <?php if(is_network_admin()): ?>
                                            <option value="">Don't override</option>
                                            <?php endif; ?>
                                            <option value="true">Enable</option>
                                            <option value="false">Disable</option>
                                        </select>
                                    </p>
                                    <p class="description"><strong ng-bind="sm.showNotice('hashify_file_name')" ></strong> Prepends a random set of numbers and letters to the filename. This is useful for preventing caching issues when uploading files that have the same filename.</p>
                                </fieldset>
                            </td>
                        </tr> 
                    </tbody>
                </table>


                <?php submit_button(); ?> 
            </form> 
        </div>
        <?php if(!is_network_admin()): ?>
        <div id="stless_sync_tab" class="stless_settings_content">
            <?php include 'regenerate_interface.php'; ?>
        </div>
        <?php endif; ?>    
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