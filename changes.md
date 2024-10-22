#### 4.1.2
* ENHANCEMENT - added `REST API Endpoint` setting, which useful when WordPress dashboard and frontend website utilize different domain names.
* ENHANCEMENT - extended `Status Info` with the information to help diagnose REST API or AJAX issues.
* COMPATIBILITY - SiteOrigin Widgets Bundle Compatibility replaced with [WP-Stateless - SiteOrigin Widgets Bundle Addon](https://wordpress.org/plugins/wp-stateless-siteorigin-widgets-bundle-addon/).
* COMPATIBILITY - WPForms Compatibility replaced with [WP-Stateless - WPForms Addon](https://wordpress.org/plugins/wp-stateless-wpforms-addon/).
* COMPATIBILITY - Easy Digital Downloads Compatibility replaced with [WP-Stateless - Easy Digital Downloads Addon](https://wordpress.org/plugins/wp-stateless-easy-digital-downloads-addon/).
* COMPATIBILITY - LiteSpeed Cache Compatibility replaced with [WP-Stateless - LiteSpeed Cache Addon](https://wordpress.org/plugins/wp-stateless-litespeed-cache-addon/).
* COMPATIBILITY - BuddyPress Compatibility replaced with [WP-Stateless - BuddyPress Addon](https://wordpress.org/support/plugin/wp-stateless-buddypress-addon/).
* FIX: PHP warning on `Status` settings tab.
* FIX: database updates to resolve conflicts with Polylang Pro compatibility.

#### 4.1.1
* FIX - cache issues during Data Optimization.

#### 4.1.0
* NEW - move compatibilities files from `wp_sm_sync` to `wp_stateless_files` table with extended information.
* COMPATIBILITY - WooCommerce Compatibility replaced with [WP-Stateless – WooCommerce Addon](https://wordpress.org/plugins/wp-stateless-woocommerce-addon/).
* COMPATIBILITY - Gravity Forms Compatibility replaced with [WP-Stateless – Gravity Forms Addon](https://wordpress.org/plugins/wp-stateless-gravity-forms-addon/).
* COMPATIBILITY - Gravity Forms Signature Compatibility replaced with [WP-Stateless – Gravity Forms Signature Addon](https://wordpress.org/plugins/wp-stateless-gravity-forms-signature-addon/).
* COMPATIBILITY - Divi Theme Compatibility replaced with [WP-Stateless – Divi Theme Addon](https://wordpress.org/plugins/wp-stateless-divi-theme-addon/).
* COMPATIBILITY - SiteOrigin CSS Compatibility replaced with [WP-Stateless – SiteOrigin CSS Addon](https://wordpress.org/plugins/wp-stateless-siteorigin-css-addon/).
* ENHANCEMENT - CLI command `wp stateless migrate` supports `auto` parameter to run all required Data Optimizations automatically.
* ENHANCEMENT - Updated Client library for Google APIs from 2.15.1 to 2.17.0.
* ENHANCEMENT - updated `firebase/php-jwt` library from from 6.9.0 to 6.10.1.
* ENHANCEMENT - updated `wpmetabox/meta-box` library from from 5.8.2 to 5.10.1.
* ENHANCEMENT - updated `deliciousbrains/wp-background-processing` library from from 1.1.1 to 1.3.1.
* ENHANCEMENT - updated `composer/installers` library from from 1.12.1 to 2.3.0.
* ENHANCEMENT - updated `Meta Box Tabs` library from 1.1.17 to 1.1.18.
* ENHANCEMENT - action `sm:sync::addFile` format changed, now it passes media object instead of file name.
* ENHANCEMENT - for installed Addons replace Download action with Activate.
* ENHANCEMENT - count compatibility files from the DB instead of listing actual files to increase performance.
* FIX - CLI command `wp stateless migrate` supports `--yes` parameter to skip confirmation.
* FIX - CLI command `wp stateless migrate` correctly works with `--progress` parameter in multisite.
* FIX - fixed synchronization for Compatibility files in Stateless Mode.
* FIX - CLI command `wp stateless upgrade` fixed when running with `--b` switch.
* FIX - fixed SiteOrigin Widgets Bundle Compatibility in `Stateless` mode.
* FIX - fixed WPForms Compatibility in `Stateless` mode.
* FIX - limit index size for compatibility with different DB engines [757](https://github.com/udx/wp-stateless/issues/757).
* FIX - correctly disable `Cache-Busting` setting for Ephemeral Mode [758](https://github.com/udx/wp-stateless/issues/758), credits [@Jessedev1](https://github.com/Jessedev1).
* FIX - Data Optimization UI adjustments.

#### 4.0.4
* ENHANCEMENT - display success message after copying Status Info.
* FIX - `Settings` page does not open or slow when there is big amount of attachments.
* FIX - in multisite network, removing custom tables properly when deleting site.
* FIX - skip setting ACL in Stateless mode and during Sync for the buckets with Uniform access, support WP_STATELESS_SKIP_ACL_SET constant [#712](https://github.com/udx/wp-stateless/issues/712).

#### 4.0.3
* NEW - added `Info` section to the `Status` tab on the Settings page, which contains the system info and the ability to copy report to clipboard.  
* ENHANCEMENT - added `Documentation` link on the Plugins page.
* ENHANCEMENT - added `Addons` link on the Plugins page.
* ENHANCEMENT - added `Documentation` link on the Settings page.
* FIX - fixed `Settings` shortcut on the Plugins page.
* FIX - in multisite network, do not show Data Optimization on the Network Admin Page.
* FIX - properly set `Content Disposition` fields for media objects.
* FIX - properly use `Cache Control` setting for media objects.
* FIX - fixed `Creation of dynamic property` PHP deprecation notice.
* FIX - fixed `Cannot use ::class with dynamic class name` PHP warning.
* FIX - avoid PHP warning when unable to get file path in `Stateless` mode [728](https://github.com/udx/wp-stateless/issues/728).
* FIX - fixed links to the constants documentation.

#### 4.0.2
* FIX - in multisite network, deleting site can potentially remove WP-Stateless tables from another site.
* COMPATIBILITY - Gravity Forms Compatibility updated for the newest Gravity Forms version.

#### 4.0.1
* FIX - improvements to Data Optimization process.
* FIX - Data Optimization fixed for multisite environment.

#### 4.0.0
* NEW - use custom database tables to store GCS file data. This increases plugin performance and will be used for future improvements.  
* NEW - added filter `wp_stateless_get_file`, retrieves the GCS file data, should be used instead of getting `sm_cloud` postmeta directly. 
* NEW - added filter `wp_stateless_get_file_sizes`, retrieves the GCS file data for image sizes, should be used instead of getting `sm_cloud` postmeta directly.
* NEW - added filter `wp_stateless_get_file_meta`, retrieves all GCS file meta data, should be used instead of getting `sm_cloud` postmeta directly.
* NEW - added filter `wp_stateless_get_file_meta_value`, retrieves the GCS file meta data by meta_key, should be used instead of getting `sm_cloud` postmeta directly.
* NEW - added filter `wp_stateless_get_setting_...` which allows to override any WP-Stateless setting. 
* NEW - added setting "Send Status Emails" allowing to change email for WP-Stateless notifications.
* NEW - added setting "Use Post Meta" allowing to switch back to using `postmeta` instead of custom DB tables. Can be used in case of issues after upgrading to 4.0.0.
* NEW - added new Settings tab `Addons`, which contains the list of WP-Stateless Addons, which replace Compatibilities.
* NEW - added new Settings tab `Status`, which contains status and health information related to Google Cloud Storage and WP-Stateless.
* NEW - CLI command `wp stateless migrate` to list and operate data optimizations.
* NEW - configuration constant [`WP_STATELESS_POSTMETA`](https://stateless.udx.io/docs/constants/#wp_stateless_postmeta) allows to read the GCS file data from postmeta instead of the new custom database tables.
* NEW - configuration constant [`WP_STATELESS_BATCH_HEALTHCHECK_INTERVAL`](https://stateless.udx.io/docs/constants/#wp_stateless_batch_healthcheck_interval) defines an interval in minutes for periodical health checks of a batch background process (like data optimization).
* COMPATIBILITY - BuddyBoss Compatibility replaced with [WP-Stateless – BuddyBoss Platform Addon](https://wordpress.org/plugins/wp-stateless-buddyboss-platform-addon/).
* COMPATIBILITY - Elementor Compatibility replaced with [WP-Stateless – Elementor Website Builder Addon](https://wordpress.org/plugins/wp-stateless-elementor-website-builder-addon/).
* COMPATIBILITY - Gravity Form Compatibility does not support older version of Gravity Forms (< 2.3).
* ENHANCEMENT - Allow dismissing notices in Admin Panel only for logged in users.
* ENHANCEMENT - Updated `wp-background-processing` library from from 1.0.2 to 1.1.1.
* ENHANCEMENT - Updated `phpseclib` 3.0.34 to 3.0.37.
* FIX - proper use of infinite timeout in `set_time_limit` function to avoid issues with PHP 8.1 and above [#704](https://github.com/udx/wp-stateless/issues/704).

#### 3.4.1
* FIX - improve security while processing AJAX requests in Admin Panel

#### 3.4.0
* ENHANCEMENT - removed `udx/lib-settings` package dependency for security reasons. 
* ENHANCEMENT - removed `udx/lib-utility` package dependency for security reasons.
* ENHANCEMENT - refactored `Settings` admin page to remove Angular dependency.
* ENHANCEMENT - including Software Bill of Materials (SBOM) to GitHub release.
* FIX - updated package dependencies for Google Client Library for security reasons.
* FIX - replaced `utf8_encode` with `mb_convert_encoding` to support PHP 8.2 and above [#678](https://github.com/udx/wp-stateless/issues/678).
* FIX - Fatal Error in `Stateless` mode if GCP access credentials are wrong [#693](https://github.com/udx/wp-stateless/issues/693).
* COMPATIBILITY - preventing PHP warnings while working with WooCommerce version 8.4.0 and above [696](https://github.com/udx/wp-stateless/issues/696).
* COMPATIBILITY - avoiding conflicts between builtin compatibilities and WP-Stateless Addon plugins.

#### 3.3.0
* NEW - Added new filter `wp_stateless_attachment_url`. Allows to customize attachment URL after WP-Stateless generates it based on it's internal conditions.
* FIX - Stateless mode Incompatible with Media Uploader in Media Library Grid mode [#675](https://github.com/udx/wp-stateless/issues/675).
* FIX - Prevent duplicating messages in Admin Panel.
* COMPATIBILITY - Dynamic Image Support is now part of the core.
* COMPATIBILITY - Google App Engine is now part of the core. Automatically enables **Stateless** mode when Google App Engine detected. Can be disabled using `WP_STATELESS_COMPATIBILITY_GAE` constant.
* COMPATIBILITY - Removed compatibility with "Advanced Custom Fields: Image Crop Add-on", because plugin is deprecated.
* COMPATIBILITY - Removed compatibility with "VidoRev" plugin.
* COMPATIBILITY - Removed compatibility with "WP Retina 2x" plugin.
* ENHANCEMENT - Updated Client library for Google APIs from 2.15.0 to 2.15.1.
* ENHANCEMENT - Updated Meta Box library from 5.6.3 to 5.8.2.
* ENHANCEMENT - Updated Meta Box Tabs to version 1.1.17.
* ENHANCEMENT - Updated PHP JWT library from 6.6.0 to 6.9.0.

#### 3.2.5
* FIX - Folder setting does not allow custom structure [#608](https://github.com/udx/wp-stateless/issues/608).
* FIX - Stateless mode Incompatible with Inline Uploader [#675](https://github.com/udx/wp-stateless/issues/675).
* FIX - html tags incorrectly applied in notice [#680](https://github.com/udx/wp-stateless/issues/680).
* ENHANCEMENT - Add WP_STATELESS_SKIP_ACL_SET for skip ACL set for GCS [#625](https://github.com/udx/wp-stateless/issues/625).
* COMPATIBILITY - Add support for The Events Calendar [#599](https://github.com/udx/wp-stateless/issues/599).

#### 3.2.4

- FIX - Website unresponsive after Upgrade [#669](https://github.com/udx/wp-stateless/issues/669).

#### 3.2.3

- ENHANCEMENT - Updated Client library for Google APIs.
- ENHANCEMENT - Updated Monolog library to version 3.
- ENHANCEMENT - Updated JWT library.
- FIX - Fixed vulnerability issues.
- FIX - Fixed an errors and warnings on PHP 8.1.
- FIX - Fixed an error that occured when WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE is set.

#### 3.2.2

- FIX -  Folder setting can't be saved from the settings page [#639](https://github.com/udx/wp-stateless/issues/639).

#### 3.2.1

- FIX - Updated requirments.
- FIX - WP-Stateless 3.2.0 doesn’t upload docs, only images [#638](https://github.com/udx/wp-stateless/issues/638).

#### 3.2.0

- ENHANCEMENT - Upgraded `wpmetabox` library.
- ENHANCEMENT - Updated Client library for Google APIs.
- ENHANCEMENT - Updated Guzzle library to version 7.
- ENHANCEMENT - Updated JWT library.
- ENHANCEMENT - Updated `license` functionality, removed `update checker`.
- FIX - Fixed vulnerability issues.
- FIX - Fixed erros and warnings on PHP 8.
- FIX - problem after the upgrade [#628](https://github.com/udx/wp-stateless/issues/628).
- FIX - image_downsize() PHP8 Required parameter $id follows optional parameter $false [#619](https://github.com/udx/wp-stateless/issues/619).

#### 3.1.1

- ENHANCEMENT - Notification for the administrator about finished synchronization. GitHub issue [#576](https://github.com/udx/wp-stateless/issues/576).
- FIX - Fixed an issue with PDF thumbnails. GitHub issue [#577](https://github.com/udx/wp-stateless/issues/577).
- FIX - Fixed an issue with synchronization in `Stateless` mode. GitHub issue [#575](https://github.com/udx/wp-stateless/issues/575).
- COMPATIBILITY - Changed the way compatibility files are stored on Multisite. GitHub issue [#588](https://github.com/udx/wp-stateless/issues/588).

#### 3.1.0

- NEW - Completely rewritten the synchronization tool. GitHub issue [#523](https://github.com/udx/wp-stateless/issues/523).
- NEW - New configuration constant `WP_STATELESS_SYNC_MAX_BATCH_SIZE`. Sets the maximum size of a background sync batch of items to be saved in a single row in the database. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_max_batch_size).
- NEW - New configuration constant `WP_STATELESS_SYNC_LOG`. Sets a path to a log file where to output logging information during the background sync. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_log).
- NEW - New configuration constant `WP_STATELESS_SYNC_HEALTHCHECK_INTERVAL`. Defines an interval in minutes for a cron task that periodically checks the health of a particular background sync process. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_healthcheck_interval).
- FIX - Fixed an issue when original files were not deleted from the server in the Ephemeral mode. GitHub issue [#484](https://github.com/udx/wp-stateless/issues/484).
- FIX - Fixed an incorrect behavior of image `srcset` attribute in the Backup mode. GitHub issue [#558](https://github.com/udx/wp-stateless/issues/558).
- COMPATIBILITY - Litespeed Cache - Fixed an incorrect upload folder determination. GitHub issue [#527](https://github.com/udx/wp-stateless/issues/527).

#### 3.0.4

- FIX - Fixed inability to use dashes in the upload folder name. GitHub issue [#565](https://github.com/udx/wp-stateless/issues/565).
- COMPATIBILITY - Elementor - Fixed wrong upload directory. GitHub issue [#560](https://github.com/udx/wp-stateless/issues/560).

#### 3.0.3

- FIX - Fixed an incorrect file URL in Stateless mode on Edit Media screen. GitHub issue [#544](https://github.com/udx/wp-stateless/issues/544).

#### 3.0.2

- FIX - Refactored the way files are being uploaded to GCS when `WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE` constant is defined. GitHub issue [#553](https://github.com/udx/wp-stateless/issues/553).
- FIX - Fixed the process of upgrading to 3.0 for multisite installations. GitHub issue [#549](https://github.com/udx/wp-stateless/issues/549).

#### 3.0.1

- FIX - Fatal Error in Stateless mode. GitHub issue [#546](https://github.com/udx/wp-stateless/issues/546).

#### 3.0

- NEW - Setup assistant rewrite. GitHub issue [#477](https://github.com/udx/wp-stateless/issues/477).
- NEW - Recreate attachment metabox panel using metabox.io. GitHub issue [#470](https://github.com/udx/wp-stateless/issues/470).
- NEW - Updated the `Stateless` mode to not use local storage at all. Current `Stateless` mode setting mapped to new `Ephemeral` mode. GitHub issue [#482](https://github.com/udx/wp-stateless/issues/482).
- NEW - Files are now uploaded to GCS in chunks and chunk size will be determined based on free memory available. GitHub issue [#478](https://github.com/udx/wp-stateless/issues/478).
- NEW - File upload chunk size can be controlled with `WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE` constant. GitHub issue [#478](https://github.com/udx/wp-stateless/issues/478).
- FIX - Changed the default value for the Cache-Busting setting. GitHub issue [#361](https://github.com/udx/wp-stateless/issues/361).
- FIX - Fixed network override of Cache-Busting. GitHub issue [#468](https://github.com/udx/wp-stateless/issues/468).
- FIX - Fixed "Passing glue string after array is deprecated.". GitHub issue [#444](https://github.com/udx/wp-stateless/issues/444).
- FIX - Fixed Compatibility default value in multisite. GitHub issue [#464](https://github.com/udx/wp-stateless/issues/464).
- FIX - Fixed multisite wrong GCS path. GitHub issue [#407](https://github.com/udx/wp-stateless/issues/407).
- FIX - Don't check for Google Cloud Storage connectivity in stateless mode unless uploading. GitHub issue [#442](https://github.com/udx/wp-stateless/issues/442).
- COMPATIBILITY - Google App Engine - Added new compatibility support for Google App Engine. [#486](https://github.com/udx/wp-stateless/issues/486)
- COMPATIBILITY - Elementor - Fixed wrong MIME type for CSS files. GitHub issue [#395](https://github.com/udx/wp-stateless/issues/395).
- COMPATIBILITY - Polylang - Fixed missing metadata issue. GitHub issue [#378](https://github.com/udx/wp-stateless/issues/378).
- COMPATIBILITY - EWWW - Fixed mime type for WEBP images. GitHub issue [#371](https://github.com/udx/wp-stateless/issues/371).
- COMPATIBILITY - Simple Local Avatars - Added new compatibility support for Simple Local Avatars. GitHub issue [#297](https://github.com/udx/wp-stateless/issues/297).
- COMPATIBILITY - BuddyPress - Fixed BuddyPress compatibility. GitHub issue [#275](https://github.com/udx/wp-stateless/issues/275).
- COMPATIBILITY - Divi - Fixed Divi cache issue. GitHub issue [#430](https://github.com/udx/wp-stateless/issues/430).
- COMPATIBILITY - Gravity Forms - add compatibility for Gravity Forms Signature Add-On. [#501](https://github.com/udx/wp-stateless/issues/501).
- COMPATIBILITY - Litespeed - Fixed fatal error and warnings. [#491](https://github.com/udx/wp-stateless/issues/491).
- COMPATIBILITY - Imagify - Added support for webp. [#403](https://github.com/udx/wp-stateless/issues/403).
- ENHANCEMENT - Update Client library for Google APIs. [#446](https://github.com/udx/wp-stateless/issues/446).
- ENHANCEMENT - Wildcards for bucket folder settings. GitHub issue [#149](https://github.com/udx/wp-stateless/issues/149).
- ENHANCEMENT - Better CLI integration. GitHub issue [#447](https://github.com/udx/wp-stateless/issues/447), [#450](https://github.com/udx/wp-stateless/issues/450) and [#451](https://github.com/udx/wp-stateless/issues/451).
- ENHANCEMENT - Sync media according to new Bucket Folder settings. GitHub issue [#449](https://github.com/udx/wp-stateless/issues/449).
- ENHANCEMENT - Moved Bucket Folder setting in the File URL section. GitHub issue [#463](https://github.com/udx/wp-stateless/issues/463).
- ENHANCEMENT - Hide Regenerate and Sync with GCS when the mode is Disabled. GitHub issue [#440](https://github.com/udx/wp-stateless/issues/440).
- ENHANCEMENT - New endpoint for the Google Cloud Storage JSON API. GitHub issue [#384](https://github.com/udx/wp-stateless/issues/384).
- ENHANCEMENT - Renamed current `Stateless` mode to `Ephemeral`. GitHub issue [#481](https://github.com/udx/wp-stateless/issues/481).

#### 2.3.2

- FIX - Fixed video file doesn't get deleted from the server in `Stateless` mode. GitHub issue [#418](https://github.com/wpCloud/wp-stateless/issues/418).
- FIX - Fixed file size doesn't show under attachment details in `Stateless` mode. GitHub issue [#413](https://github.com/wpCloud/wp-stateless/issues/413).
- FIX - Fixed Cache-Busting feature works even if the Mode is `Disabled`. GitHub issue [#405](https://github.com/wpCloud/wp-stateless/issues/405).
- COMPATIBILITY - Fixed Gravity Form Post Image didn't include `Bucket Folder`. GitHub issue [#421](https://github.com/wpCloud/wp-stateless/issues/421).
- COMPATIBILITY - Fixed Divi Builder Export. GitHub issue [#420](https://github.com/wpCloud/wp-stateless/issues/420).
- COMPATIBILITY - Fixed BuddyBoss pages breaking after updating to 2.3.0. GitHub issue [#417](https://github.com/wpCloud/wp-stateless/issues/417).

#### 2.3.1

- Fix - Fixed fatal error, undefined function `is_wp_version_compatible`. GitHub issue [#414](https://github.com/wpCloud/wp-stateless/issues/414).

#### 2.3.0

- FIX - Fixed problem with WordPress 5.3. GitHub issue [#406](https://github.com/wpCloud/wp-stateless/issues/406).
- FIX - Fixed problem with the Cache Busting feature. GitHub issue [#377](https://github.com/wpCloud/wp-stateless/issues/377).
- COMPATIBILITY - Added compatibility support for WP Retina 2x pro. GitHub issue [#380](https://github.com/wpCloud/wp-stateless/issues/380).
- COMPATIBILITY - Enhanced compatibility support for LiteSpeed Cache. GitHub issue [#365](https://github.com/wpCloud/wp-stateless/issues/365).
- COMPATIBILITY - Enhanced compatibility support for ShortPixel Image Optimizer. GitHub issue [#364](https://github.com/wpCloud/wp-stateless/issues/364), [#398](https://github.com/wpCloud/wp-stateless/issues/398).
- COMPATIBILITY - Fixed Gravity Form export. GitHub issue [#408](https://github.com/wpCloud/wp-stateless/issues/408).
- ENHANCEMENT - Improved upon add_media function for better compatibility support. GitHub issue [#382](https://github.com/wpCloud/wp-stateless/issues/382).

#### 2.2.7

- FIX - WP-Smush compatibility enhanced. GitHub Issue [#366](https://github.com/wpCloud/wp-stateless/issues/366).
- FIX - Fixed multisite installation support. GitHub Issue [#370](https://github.com/wpCloud/wp-stateless/issues/370).
- FIX - Fixed settings UI problems related to Cache-Busting option. GitHub Issue [#373](https://github.com/wpCloud/wp-stateless/issues/373).
- FIX - Other minor fixes.

#### 2.2.6

- FIX - Multisite Network Settings page fixed. GitHub Issue [#369](https://github.com/wpCloud/wp-stateless/issues/369).
- FIX - Fixed incorrect Compatibilities behavior when Bucket Folder is set. GitHub Issue [#368](https://github.com/wpCloud/wp-stateless/issues/368).
- FIX - Other minor fixes.

#### 2.2.5

- NEW - Added ability to start sync process from specific Attachment ID. GitHub Issue [#360](https://github.com/wpCloud/wp-stateless/issues/360).
- COMPATIBILITY - Added compatibility support for LiteSpeed Cache plugin. Especially to support optimized .webp images. GitHub Issue [#357](https://github.com/wpCloud/wp-stateless/issues/357).
- FIX - Other minor fixes.

#### 2.2.4

- NEW - Added new filter `wp_stateless_skip_add_media`. Allows skipping synchronization of the media object with GCS depending on custom condition. GitHub Issue [#344](https://github.com/wpCloud/wp-stateless/issues/344).
- FIX - Compatibility Manager is considering Child Themes now. GitHub Issue [#351](https://github.com/wpCloud/wp-stateless/issues/351).
- FIX - Custom domains handling has been fixed. GitHub Issue [#358](https://github.com/wpCloud/wp-stateless/issues/358).
- ENHANCEMENT - Imagify Image Optimizer and WP Smush compatibilities improved. GitHub Issue [#359](https://github.com/wpCloud/wp-stateless/issues/359).

#### 2.2.3

- FIX - `get_post_metadata` does not break multi-dimensional arrays anymore. GitHub Issue [#352](https://github.com/wpCloud/wp-stateless/issues/352).
- FIX - `PHP Warning: substr_compare()` fixed. GitHub Issue [#350](https://github.com/wpCloud/wp-stateless/issues/350).
- FIX - Filtering Domain setting before saving in order to get rid of possible empty spaces. GitHub Issue [#348](https://github.com/wpCloud/wp-stateless/issues/348).
- FIX - Incorrect remote file path generated when disabled Organization setting. GitHub Issue [#343](https://github.com/wpCloud/wp-stateless/issues/343).
- FIX - Hiding admin notices correctly. GitHub Pull Request [#355](https://github.com/wpCloud/wp-stateless/pull/355).

#### 2.2.2

- FIX - Proper 'srcset' attribute handling. GitHub Issue [#342](https://github.com/wpCloud/wp-stateless/issues/342).
- ENHANCEMENT - Minor fixes code quality.

#### 2.2.1

- Fix - Security patch for Authenticated Remote Code Execution (RCE) vulnerability.

#### 2.2.0

- FIX - Slow page generation when File URL Replacement is enabled [#265](https://github.com/wpCloud/wp-stateless/issues/265).
- FIX - Fatal error when WP Smush Pro compatibility is enabled [#325](https://github.com/wpCloud/wp-stateless/issues/325).
- FIX - Issue with Imagify [#326](https://github.com/wpCloud/wp-stateless/issues/326).
- FIX - Return correct srcset images [#328](https://github.com/wpCloud/wp-stateless/issues/328).
- FIX - Fatal error with GFForms [#330](https://github.com/wpCloud/wp-stateless/issues/330).
- FIX - Typo in admin notices [#337](https://github.com/wpCloud/wp-stateless/issues/337).
- ENHANCEMENT - Extended “File URL Replacement” options [#336](https://github.com/wpCloud/wp-stateless/issues/336).
- ENHANCEMENT - Service Account JSON is now hidden if set via constant [#320](https://github.com/wpCloud/wp-stateless/issues/320).
- ENHANCEMENT - New database table for tracking files not tracked in media library [#307](https://github.com/wpCloud/wp-stateless/issues/307).
- ENHANCEMENT - Updated depreciated function flagged by security software [#300](https://github.com/wpCloud/wp-stateless/issues/300).

#### 2.1.9

- FIX - Resolved fatal error with OneCodeShop RML Amazon S3 plugin. GitHub Issue [#317](https://github.com/wpCloud/wp-stateless/issues/317).
- FIX - Resolved missing bucket in file URL when “storage.googleapis.com” was supplied in Domain field. GitHub Issue [#318](https://github.com/wpCloud/wp-stateless/issues/318).
- ENHANCEMENT - Support synchronization of files without metadata, such as .doc and .docx files. GitHub Issue [#316](https://github.com/wpCloud/wp-stateless/issues/316).

#### 2.1.8

- FIX - WooCommerce product export.
- FIX - PDF previews in media library now supported.
- ENHANCEMENT - Improved error message when there is nothing to sync.
- ENHANCEMENT - Renamed constant WP_STATELESS_MEDIA_HASH_FILENAME to WP_STATELESS_MEDIA_CACHE_BUSTING.
- ENHANCEMENT - Domain field functionality now allows webmaster to control http or https
- ENHANCEMENT - Notice about Stateless mode requiring the Cache-Busting option is displayed to those using Stateless mode.
- ENHANCEMENT - Upload full size image before generating thumbnails.
- COMPATIBILITY - Added compatibility support for Learndash plugin.
- COMPATIBILITY - Added compatibility support for BuddyPress plugin.
- COMPATIBILITY - Added compatibility support for Divi Builder export.
- COMPATIBILITY - Added compatibility support for Elementor plugin.

#### 2.1.7

- ENHANCEMENT - Display dashboard-wide notice for existing users explaining stateless mode now enables cache-busting option.
- ENHANCEMENT - Display notice when selecting stateless mode explaining stateless mode now enables cache-busting option.
- ENHANCEMENT - Display required message on cache-busting setting description when stateless mode is enabled.

#### 2.1.6

- FIX - Resolved Google SDK conflict.
- FIX - ICompatibility.php errors notice.
- FIX - Undefined index: gs_link in class-bootstrap.php.
- FIX - Media files with accent characters would not upload correctly to the bucket.
- ENHANCEMENT - Force `Cache-Busting` when using `Stateless` mode.
- ENHANCEMENT - New admin notice design.
- ENHANCEMENT - Improved and clear error message.
- ENHANCEMENT - Renamed constant `WP_STATELESS_MEDIA_ON_FLY` to `WP_STATELESS_DYNAMIC_IMAGE_SUPPORT`.
- ENHANCEMENT - Update Google Libraries.
- ENHANCEMENT - Renamed constant `WP_STATELESS_MEDIA_HASH_FILENAME` to `WP_STATELESS_MEDIA_CACHE_BUSTING`.
- COMPATIBILITY - Renamed constant `WP_STATELESS_COMPATIBILITY_WPSmush` to `WP_STATELESS_COMPATIBILITY_WPSMUSH`.
- COMPATIBILITY - Added support for `WooCommerce Extra Product Options`.
- COMPATIBILITY - Added support for `WPForms Pro`.
- COMPATIBILITY - Improved `ShortPixel` compatibility.
- COMPATIBILITY - Fixed `ACF Image Crop` compatibility.

#### 2.1.5

- FIX - Fatal error with PHP 5.4.45 on activation.
- FIX - E_WARNING: Illegal string offset ‘gs_bucket’.
- FIX - Resolved ‘save_network_settings’ message when saving network settings.
- COMPATIBILITY - Added support for WP Forms plugin
- COMPATIBILITY - Added support for WP Smush plugin
- COMPATIBILITY - Added support for ShortPixel Image Optimizer plugin.
- COMPATIBILITY - Added support for Imagify Image Optimizer plugin.
- COMPATIBILITY - Added support for SiteOrigin CSS plugin.
- COMPATIBILITY - Added support for Gravity Forms plugin.
- COMPATIBILITY - Added support for WPBakery Page Builder plugin.
- COMPATIBILITY - Added wp-config constant support for compatibility options.

#### 2.1.4

- ENHANCEMENT - Updated Google OAuth URL for Setup Assistant.

#### 2.1.3

- ENHANCEMENT - Updates to text explainers in Setup Assistant.
- ENHANCEMENT - Refined redirection logic when activating plugin.
- FIX - Removed extra space in converted URLs.

#### 2.1.2

- ENHANCEMENT - Improved support for Easy Digital Downloads.
- ENHANCEMENT - Added constant WP_STATELESS_CONSOLE_LOG check before logging to console.
- ENHANCEMENT - Changed service account default permissions on creation.
- COMPATIBILITY - Added support for SiteOrigin generated CSS files.
- ENHANCEMENT - Moved Dynamic Image Support to Capability tab.
- COMPATIBILITY - Added support for ACF Image Crop addon.
- FIX - Fixed compatibility issue with wp-smush plugin.
- FIX - Added required blog param for multi-sites.
- FIX - Updated media library and mediaItem API endpoints.
- COMPATIBILITY - Added support for EDD download method option.

#### 2.1.1

- FIX - Fixed double slash when Organization is disabled.
- FIX - Fatal error with GuzzleHttp.
- FIX - Fixed content-type assignment.
- ENHANCEMENT - Added support for https URLs in Domain field.
- COMPATIBILITY - Advanced Custom Fields Image Crop Addon.

#### 2.1.0

- FIX - Fixed read only for Service Account JSON if constant or environment variable is defined.
- FIX - Override default cache control.
- FIX - Fixed custom domain bucket support with setup assistant.
- FIX - Improved support for wp_calculate_image_srcset.
- FIX - Synchronizing non-image files will now delete the local copy.
- NEW - Support for GOOGLE_APPLICATION_CREDENTIALS environment variable.
- NEW - Added bucket region option to setup assistant.
- NEW - Added custom file type support for File URL Replacement setting.
- NEW - Added failover to image url when not found on disk for sync tool.
- ENHANCEMENT - updated service account role to Storage Object Admin.

#### 2.0.3

- FIX - Fixed Fatal Error which was occurring on WordPress Multisite after upgrading plugin from 1.x to 2.x.
- ENHANCEMENT - Improved support of PDF files.

#### 2.0.2

- FIX - Fixed Fatal Errors which were caused by using PHP 5.4 and less.
- FIX - Fixed Fatal Error which was caused on Media page when WP Smush Pro plugin is activated.
- FIX - Fixed detection of plugin files paths. The issue was occurring on installations with custom file structures ( e.g. Bedrock platform ).
- FIX - Fixed redirection URL to Setup Wizard on plugin activation.
- ENHANCEMENT - Updated the minimum requirements for PHP to 5.5 to prevent fatal errors and possible warnings.

#### 2.0.1

- ENHANCEMENT - Added compatibility with Google SDK v1.x version to prevent conflicts with third-party plugins.
- ENHANCEMENT - Added warning message if old Google SDK version is loaded by third-party plugin.

#### 2.0.0

- NEW - Added stateless mode.
- NEW - Dedicated settings panel.
- NEW - Setup assistant for initial plugin activation.
- NEW - Support for replacing default GCS domain with a custom domain.
- ENHANCEMENT - Expanded network setting overrides.
- ENHANCEMENT - Expanded wp-config constants.
- ENHANCEMENT - Relocated synchronization and regeneration tools to the new settings panel.

#### 1.9.2

- ENHANCEMENT - Added ability to modify default bucket link via 'wp_stateless_bucket_link' filter.
- ENHANCEMENT - Added checking of connection to GCS once per four hours instead of doing it on every page load.
- ENHANCEMENT - Google SDK was moved from vendor dir. So it's not loaded on every page load anymore, but only when it's required.
- ENHANCEMENT - Updated Composer Autoload logic.
- ENHANCEMENT - Reverted all changes included to 1.9.1 version because of conflicts.

#### 1.9.0

- Added new ability to define cacheControl for remote objects.
- Added new option that adds random hashes to file names.

#### 1.8.0

- Added the ability to regenerate and synchronize separate Media file from the list.
- Added the ability to regenerate and synchronize Media file from edit screen.
- Fixed the issue on multisite setup (switch_to_blog now works as expected).
- Performance fixes.
- UI cleanup.

#### 1.7.3

- Added ability to fix previously failed items.

#### 1.7.1

- Migrated from p12 to JSON.
- New feature of media sync.
- New option Root Directory.
- Optimized uploading process.
- Other options.

#### 1.7.0

- Fixed conflict with SSL forcing plugin.

#### 1.6.0

- Plugin Updates ability added.
- WordPress 4.4 compatibility fixed.
- Post content filter fixed.

#### 1.5.0

- Migration into wp-stateless from wp-stateless-media.
- Version bump to resolve bucket permissions issues.

#### 1.3.2

- Fixed issue with wp_normalize_path causing a fatal error on older installs.
- Removed \$\_SESSION usage for transients.

#### 1.3.1

- Added ability to upload non-images to bucket.
- Fixed issue with running batches in WP CLI

#### 1.3.0

- Added WP CLI functionality.
- Added WP CLI command to move all legacy meta data to serialized array.
- Changed the way of storing SM cloud meta data.

#### 1.2.0

- Added Imagemagic/GD check to warn admin that thumbnails will not be generated.
- Added mediaLink, mediaLink and id storage for uploaded objects.
- Added support for cacheControl with default settings based on Mime type of upload.
- Added sm:item:cacheControl and sm:item:contentDisposition filters.
- Cleaned-up metadata that is made available to GCS and visible in response headers.
- Removed app_name, using blog domain name automatically.
- Added Cache Control to media editor.

#### 1.1.0

- Added support for <code>WP_STATELESS_MEDIA_MODE</code>.
- Renamed constants <code>STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>STATELESS_MEDIA_KEY_FILE_PATH</code> to <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.2

- Added a _view_ link to media edit page for synchronized items.
- Added some _wp_get_attachment_image_ dynamic attributes.

#### 1.0.1

- Added Network Management option.
- Added support for <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.0

- Set branch to v1.0.
- Removed autocompletion from Email Address and Application Name fields.
- Moved lib/classes into just lib.
- Rename class-Bootstrap.php to be lowercased.
- Added composer/installers to composer.json 'required' dependencies.
- Rename stateless-media.php to wp-stateless-media.php so there aren't unexpected plugin basename issues.
- Change package name to wp-stateless-media in package.json
- Rename 'wpCloud/wp-stateless-media' to 'wpcloud/wp-stateless-media' in composer.json, Composer does not allow uppercase names.

#### 0.2.1

- Added 'Settings' link to plugin list.
