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
