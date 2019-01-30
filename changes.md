#### 2.2.2 ####
* FIX - Proper 'srcset' attribute handling. GitHub Issue [#342](https://github.com/wpCloud/wp-stateless/issues/342).
* ENHANCEMENT - Minor fixes code quality.

#### 2.2.1 ####
* Fix - Security patch for Authenticated Remote Code Execution (RCE) vulnerability.

#### 2.2.0 ####
* FIX - Slow page generation when File URL Replacement is enabled [#265](https://github.com/wpCloud/wp-stateless/issues/265). 
* FIX - Fatal error when WP Smush Pro compatibility is enabled [#325](https://github.com/wpCloud/wp-stateless/issues/325). 
* FIX - Issue with Imagify [#326](https://github.com/wpCloud/wp-stateless/issues/326). 
* FIX - Return correct srcset images [#328](https://github.com/wpCloud/wp-stateless/issues/328). 
* FIX - Fatal error with GFForms [#330](https://github.com/wpCloud/wp-stateless/issues/330). 
* FIX - Typo in admin notices [#337](https://github.com/wpCloud/wp-stateless/issues/337). 
* ENHANCEMENT - Extended “File URL Replacement” options [#336](https://github.com/wpCloud/wp-stateless/issues/336). 
* ENHANCEMENT - Service Account JSON is now hidden if set via constant [#320](https://github.com/wpCloud/wp-stateless/issues/320). 
* ENHANCEMENT - New database table for tracking files not tracked in media library [#307](https://github.com/wpCloud/wp-stateless/issues/307). 
* ENHANCEMENT - Updated depreciated function flagged by security software [#300](https://github.com/wpCloud/wp-stateless/issues/300). 

#### 2.1.9 ####
* FIX - Resolved fatal error with OneCodeShop RML Amazon S3 plugin. GitHub Issue [#317](https://github.com/wpCloud/wp-stateless/issues/317).
* FIX - Resolved missing bucket in file URL when “storage.googleapis.com” was supplied in Domain field. GitHub Issue [#318](https://github.com/wpCloud/wp-stateless/issues/318).
* ENHANCEMENT - Support synchronization of files without metadata, such as .doc and .docx files. GitHub Issue [#316](https://github.com/wpCloud/wp-stateless/issues/316).

#### 2.1.8 ####
* FIX - WooCommerce product export.
* FIX - PDF previews in media library now supported.
* ENHANCEMENT - Improved error message when there is nothing to sync.
* ENHANCEMENT - Renamed constant WP_STATELESS_MEDIA_HASH_FILENAME to WP_STATELESS_MEDIA_CACHE_BUSTING.
* ENHANCEMENT - Domain field functionality now allows webmaster to control http or https
* ENHANCEMENT - Notice about Stateless mode requiring the Cache-Busting option is displayed to those using Stateless mode.
* ENHANCEMENT - Upload full size image before generating thumbnails.
* COMPATIBILITY - Added compatibility support for Learndash plugin.
* COMPATIBILITY - Added compatibility support for BuddyPress plugin.
* COMPATIBILITY - Added compatibility support for Divi Builder export.
* COMPATIBILITY - Added compatibility support for Elementor plugin.

#### 2.1.7 ####
* ENHANCEMENT - Display dashboard-wide notice for existing users explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display notice when selecting stateless mode explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display required message on cache-busting setting description when stateless mode is enabled.

#### 2.1.6 ####
* FIX - Resolved Google SDK conflict.
* FIX - ICompatibility.php errors notice.
* FIX - Undefined index: gs_link in class-bootstrap.php.
* FIX - Media files with accent characters would not upload correctly to the bucket. 
* ENHANCEMENT - Force `Cache-Busting` when using `Stateless` mode. 
* ENHANCEMENT - New admin notice design.
* ENHANCEMENT - Improved and clear error message. 
* ENHANCEMENT - Renamed constant `WP_STATELESS_MEDIA_ON_FLY` to `WP_STATELESS_DYNAMIC_IMAGE_SUPPORT`. 
* ENHANCEMENT - Update Google Libraries.
* ENHANCEMENT - Renamed constant `WP_STATELESS_MEDIA_HASH_FILENAME` to `WP_STATELESS_MEDIA_CACHE_BUSTING`.
* COMPATIBILITY - Renamed constant `WP_STATELESS_COMPATIBILITY_WPSmush` to `WP_STATELESS_COMPATIBILITY_WPSMUSH`.
* COMPATIBILITY - Added support for `WooCommerce Extra Product Options`.
* COMPATIBILITY - Added support for `WPForms Pro`.
* COMPATIBILITY - Improved `ShortPixel` compatibility.
* COMPATIBILITY - Fixed `ACF Image Crop` compatibility.

#### 2.1.5 ####
* FIX - Fatal error with PHP 5.4.45 on activation.
* FIX - E_WARNING: Illegal string offset ‘gs_bucket’.
* FIX - Resolved ‘save_network_settings’ message when saving network settings.
* COMPATIBILITY - Added support for WP Forms plugin
* COMPATIBILITY - Added support for WP Smush plugin
* COMPATIBILITY - Added support for ShortPixel Image Optimizer plugin.
* COMPATIBILITY - Added support for Imagify Image Optimizer plugin.
* COMPATIBILITY - Added support for SiteOrigin CSS plugin.
* COMPATIBILITY - Added support for Gravity Forms plugin.
* COMPATIBILITY - Added support for WPBakery Page Builder plugin.
* COMPATIBILITY - Added wp-config constant support for compatibility options.

#### 2.1.4 ####
* ENHANCEMENT - Updated Google OAuth URL for Setup Assistant.

#### 2.1.3 ####
* ENHANCEMENT - Updates to text explainers in Setup Assistant.
* ENHANCEMENT - Refined redirection logic when activating plugin.
* FIX - Removed extra space in converted URLs.

#### 2.1.2
* ENHANCEMENT - Improved support for Easy Digital Downloads.
* ENHANCEMENT - Added constant WP_STATELESS_CONSOLE_LOG check before logging to console.
* ENHANCEMENT - Changed service account default permissions on creation.
* COMPATIBILITY - Added support for SiteOrigin generated CSS files.
* ENHANCEMENT - Moved Dynamic Image Support to Capability tab.
* COMPATIBILITY - Added support for ACF Image Crop addon.
* FIX - Fixed compatibility issue with wp-smush plugin.
* FIX - Added required blog param for multi-sites.
* FIX - Updated media library and mediaItem API endpoints.
* COMPATIBILITY - Added support for EDD download method option.

#### 2.1.1
* FIX - Fixed double slash when Organization is disabled.
* FIX - Fatal error with GuzzleHttp.
* FIX - Fixed content-type assignment.
* ENHANCEMENT - Added support for https URLs in Domain field.
* COMPATIBILITY - Advanced Custom Fields Image Crop Addon.

#### 2.1.0
* FIX - Fixed read only for Service Account JSON if constant or environment variable is defined. 
* FIX - Override default cache control.
* FIX - Fixed custom domain bucket support with setup assistant.
* FIX - Improved support for wp_calculate_image_srcset.
* FIX - Synchronizing non-image files will now delete the local copy.
* NEW - Support for GOOGLE_APPLICATION_CREDENTIALS environment variable.
* NEW - Added bucket region option to setup assistant.
* NEW - Added custom file type support for File URL Replacement setting.
* NEW - Added failover to image url when not found on disk for sync tool.
* ENHANCEMENT - updated service account role to Storage Object Admin.

#### 2.0.3
* FIX - Fixed Fatal Error which was occurring on WordPress Multisite after upgrading plugin from 1.x to 2.x.
* ENHANCEMENT - Improved support of PDF files.

#### 2.0.2
* FIX - Fixed Fatal Errors which were caused by using PHP 5.4 and less.
* FIX - Fixed Fatal Error which was caused on Media page when WP Smush Pro plugin is activated.
* FIX - Fixed detection of plugin files paths. The issue was occurring on installations with custom file structures ( e.g. Bedrock platform ).
* FIX - Fixed redirection URL to Setup Wizard on plugin activation.
* ENHANCEMENT - Updated the minimum requirements for PHP to 5.5 to prevent fatal errors and possible warnings.

#### 2.0.1
* ENHANCEMENT - Added compatibility with Google SDK v1.x version to prevent conflicts with third-party plugins.
* ENHANCEMENT - Added warning message if old Google SDK version is loaded by third-party plugin.

#### 2.0.0
* NEW - Added stateless mode.
* NEW - Dedicated settings panel.
* NEW - Setup assistant for initial plugin activation.
* NEW - Support for replacing default GCS domain with a custom domain.
* ENHANCEMENT - Expanded network setting overrides.
* ENHANCEMENT - Expanded wp-config constants.
* ENHANCEMENT - Relocated synchronization and regeneration tools to the new settings panel.

#### 1.9.2
* ENHANCEMENT - Added ability to modify default bucket link via 'wp_stateless_bucket_link' filter.
* ENHANCEMENT - Added checking of connection to GCS once per four hours instead of doing it on every page load.
* ENHANCEMENT - Google SDK was moved from vendor dir. So it's not loaded on every page load anymore, but only when it's required.
* ENHANCEMENT - Updated Composer Autoload logic.
* ENHANCEMENT - Reverted all changes included to 1.9.1 version because of conflicts.

#### 1.9.0
* Added new ability to define cacheControl for remote objects.
* Added new option that adds random hashes to file names.

#### 1.8.0
* Added the ability to regenerate and synchronize separate Media file from the list.
* Added the ability to regenerate and synchronize Media file from edit screen.
* Fixed the issue on multisite setup (switch_to_blog now works as expected).
* Performance fixes.
* UI cleanup.

#### 1.7.3
* Added ability to fix previously failed items.

#### 1.7.1
* Migrated from p12 to JSON.
* New feature of media sync.
* New option Root Directory.
* Optimized uploading process.
* Other options.

#### 1.7.0
* Fixed conflict with SSL forcing plugin.

#### 1.6.0
* Plugin Updates ability added.
* WordPress 4.4 compatibility fixed.
* Post content filter fixed.

#### 1.5.0
* Migration into wp-stateless from wp-stateless-media.
* Version bump to resolve bucket permissions issues.
 
#### 1.3.2
* Fixed issue with wp_normalize_path causing a fatal error on older installs.
* Removed $_SESSION usage for transients.

#### 1.3.1
* Added ability to upload non-images to bucket.
* Fixed issue with running batches in WP CLI

#### 1.3.0
* Added WP CLI functionality.
* Added WP CLI command to move all legacy meta data to serialized array.
* Changed the way of storing SM cloud meta data.

#### 1.2.0
* Added Imagemagic/GD check to warn admin that thumbnails will not be generated.
* Added mediaLink, mediaLink and id storage for uploaded objects.
* Added support for cacheControl with default settings based on Mime type of upload.
* Added sm:item:cacheControl and sm:item:contentDisposition filters.
* Cleaned-up metadata that is made available to GCS and visible in response headers.
* Removed app_name, using blog domain name automatically.
* Added Cache Control to media editor.

#### 1.1.0
* Added support for <code>WP_STATELESS_MEDIA_MODE</code>.
* Renamed constants <code>STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>STATELESS_MEDIA_KEY_FILE_PATH</code> to <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.2
* Added a *view* link to media edit page for synchronized items.
* Added some *wp_get_attachment_image* dynamic attributes.

#### 1.0.1
* Added Network Management option.
* Added support for <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.0
* Set branch to v1.0.
* Removed autocompletion from Email Address and Application Name fields.
* Moved lib/classes into just lib.
* Rename class-Bootstrap.php to be lowercased.
* Added composer/installers to composer.json 'required' dependencies.
* Rename stateless-media.php to wp-stateless-media.php so there aren't unexpected plugin basename issues.
* Change package name to wp-stateless-media in package.json
* Rename 'wpCloud/wp-stateless-media' to 'wpcloud/wp-stateless-media' in composer.json, Composer does not allow uppercase names.

#### 0.2.1
* Added 'Settings' link to plugin list.
