=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, planvova, obolgun
Donate link: https://udx.io
Tags: google cloud, google cloud storage, cdn, uploads, backup
License: GPLv2 or later
Requires PHP: 8.0
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 4.1.2

Upload and serve your WordPress media files from Google Cloud Storage.

== Description ==

Upload and serve your WordPress media from Google Cloud Storage (GCS) with the WP-Stateless plugin. In as little as two minutes, you will be benefitting from serving your media from Google Cloud's distributed servers.

New to Google Cloud? Google is offering you a [$300 credit](https://console.cloud.google.com/freetrial?referralId=e1c28cf728ff49b38d4eb5add3f5bfc8) to get you started.

= Benefits =
* Store and deliver media files on Google Cloud Storage instead of your server.
* Google Cloud Storage is geo-redundant, meaning your media is delivered by the closest server - reducing latency and improving page speed.
* Scale your WordPress website across multiple servers without the need of synchronizing media files.
* Native integration between Google Cloud Storage and WordPress.
* $300 free trial from Google Cloud. Nice!

= Modes =
* Backup - Upload media files to Google Storage and serve local file urls.
* CDN - Copy media files to Google Storage and serve them directly from there.
* Ephemeral - Store and serve media files with Google Cloud Storage only. Media files are not stored locally, but local storage is used temporarily for processing and is required for certain compatibilities.
* Stateless - Store and serve media files with Google Cloud Storage only. Media files are not stored locally.

= Features =
* Setup assistant makes getting started fast and easy.
* No need to manually create service accounts or buckets - handled automatically.
* Settings panel provides you with further GCS configuration and file url customization.
* Mask the default GCS URL with your own custom domain.
* Automatically replace hardcoded media URLs with GCS equivalents in post editor and meta.
* Batch image thumbnail regeneration.
* Synchronization tools for uploading existing files and images.
* All settings supported with wp-config constants and network setting overrides.
* Multiple modes: Backup, CDN, Ephemeral, Stateless.
* All files served in HTTPS mode.
* Serverless platform compatible, including Google App Engine.
* Multisite compatible.

= Addons =
* [Elementor Website Builder Addon](https://wordpress.org/plugins/wp-stateless-elementor-website-builder-addon/)
* [Gravity Forms Addon](https://wordpress.org/plugins/wp-stateless-gravity-forms-addon/)
* [Gravity Forms Signature Addon](https://wordpress.org/plugins/wp-stateless-gravity-forms-signature-addon/)
* [WPForms Addon](https://wordpress.org/plugins/wp-stateless-wpforms-addon/)
* [WooCommerce Addon](https://wordpress.org/plugins/wp-stateless-woocommerce-addon/)
* [Easy Digital Downloads Addon](https://wordpress.org/plugins/wp-stateless-easy-digital-downloads-addon/)
* [LiteSpeed Cache Addon](https://wordpress.org/plugins/wp-stateless-litespeed-cache-addon/)
* [Divi Theme Addon](https://wordpress.org/plugins/wp-stateless-divi-theme-addon/)
* [SiteOrigin CSS Addon](https://wordpress.org/plugins/wp-stateless-siteorigin-css-addon/)
* [SiteOrigin Widgets Bundle Addon](https://wordpress.org/plugins/wp-stateless-siteorigin-widgets-bundle-addon/)
* [BuddyPress Addon](https://wordpress.org/support/plugin/wp-stateless-buddypress-addon/)
* [BuddyBoss Platform Addon](https://wordpress.org/plugins/wp-stateless-buddyboss-platform-addon/)

= Support, Feedback, & Contribute =
We welcome community involvement via the [GitHub repository](https://github.com/udx/wp-stateless).

= Custom Development =
Looking for a unique feature for your next project? [Hire us!](https://udx.io/)

== Installation ==

1. Search, install, and activate the *WP-Stateless* plugin via your WordPress dashboard.
2. Begin WP-Stateless setup assistant at *Media > Stateless Setup* and click "Get Started Now."
3. Click "Google Login" and sign-in with your Google account.
4. Set a Google Cloud Project, Google Cloud Storage Bucket, and Google Cloud Billing Account and click "Continue."
5. Installation and setup is now complete. Visit *Media > Stateless Settings* for more options.
For a more detailed installation and setup walkthrough, please see the [manual setup instructions on Github](https://stateless.udx.io/docs/manual-setup/).

== Screenshots ==

1. Settings Panel: Supports network setting and wp-config constant overrides.
2. Setup Assistant
3. Setup Assistant: Google Login
4. Setup Assistant: Approve Permissions
5. Setup Assistant: Project & Bucket
6. Setup Assistant: Complete
7. Edit Media: Image stored on Google Cloud Storage.

== Frequently Asked Questions ==

= What are the minimum server requirements for this plugin? =

Beyond the [official WordPress minimum requirements](https://wordpress.org/about/requirements/), WP-Stateless requires a minimum PHP version of 8.0 or higher and OpenSSL to be enabled.

= What wp-config constants are supported? =

For a complete list of supported wp-config constants, please consult the [GitHub documentation](https://stateless.udx.io/docs/constants/).

= How do I manually generate the Service Account JSON? =

The WP-Stateless setup assistant will create the Service Account JSON automatically for you, but you can follow these steps if you choose to create it manually.

1. Visit Google Cloud Console, and go to *IAM & Admin > Service accounts*.
2. Click *Create Service Account* and name it *wp-stateless*.
3. Set the role to *Storage > Storage Admin*.
4. Check *Furnish a new private key* and select *JSON* as the key type.
5. Open the JSON file and copy the contents into the *Service Account JSON* textarea within the WP-Stateless settings panel.

= Where can I submit feature requests or bug reports? =

We encourage community feedback and discussion through issues on the [GitHub repository](https://github.com/udx/wp-stateless/issues).

= Can I test new features before they are released? =

To ensure new releases cause as little disruption as possible, we rely on a number of early adopters who assist us by testing out new features before they are released. [Please contact us](https://udx.io/) if you are interested in becoming an early adopter.

= Who maintains this plugin? =

[UDX](https://udx.io/) maintains this plugin by continuing development through it's own staff, reviewing pull requests, testing, and steering the overall release schedule. UDX is located in Durham, North Carolina and provides WordPress engineering and hosting services to clients throughout the United States.


== Upgrade Notice ==
= 4.1.0 =
You will be prompted to run data optimization after upgrade. Please make a backup copy of your database.

= 4.0.0 =
You will be prompted to run data optimization after upgrade. Please make a backup copy of your database.
If you using BuddyBoss Platform you will be proposed to install [WP-Stateless – BuddyBoss Platform Addon](https://wordpress.org/plugins/wp-stateless-buddyboss-platform-addon/), which replaces BuddyBoss Compatibility.
If you using Elementor Website Builder you will be proposed to install [WP-Stateless – Elementor Website Builder Addon](https://wordpress.org/plugins/wp-stateless-elementor-website-builder-addon/), which replaces Elementor Compatibility.

= 3.2.3 =
Before upgrading to WP-Stateless 3.2.3, please, make sure you use PHP 8.0 or above.

= 3.2.0 =
Before upgrading to WP-Stateless 3.2.0, please, make sure you use PHP 7.2 or above.

= 3.0 =
Before upgrading to WP-Stateless 3.0, please, make sure you tested it on your development environment.

== Changelog ==
= 4.1.2 =
* ENHANCEMENT - added `REST API Endpoint` setting, which useful when WordPress dashboard and frontend website utilize different domain names.
* ENHANCEMENT - extended `Status Info` with the information to help diagnose REST API or AJAX issues.
* COMPATIBILITY - SiteOrigin Widgets Bundle Compatibility replaced with [WP-Stateless - SiteOrigin Widgets Bundle Addon](https://wordpress.org/plugins/wp-stateless-siteorigin-widgets-bundle-addon/).
* COMPATIBILITY - WPForms Compatibility replaced with [WP-Stateless - WPForms Addon](https://wordpress.org/plugins/wp-stateless-wpforms-addon/).
* COMPATIBILITY - Easy Digital Downloads Compatibility replaced with [WP-Stateless - Easy Digital Downloads Addon](https://wordpress.org/plugins/wp-stateless-easy-digital-downloads-addon/).
* COMPATIBILITY - LiteSpeed Cache Compatibility replaced with [WP-Stateless - LiteSpeed Cache Addon](https://wordpress.org/plugins/wp-stateless-litespeed-cache-addon/).
* COMPATIBILITY - BuddyPress Compatibility replaced with [WP-Stateless - BuddyPress Addon](https://wordpress.org/support/plugin/wp-stateless-buddypress-addon/).
* FIX: remove PHP warning on `Status` settings tab.
* FIX: database updates to resolve conflicts with Polylang Pro compatibility.

= 4.1.1 =
* FIX - cache issues during Data Optimization.

= 4.1.0 =
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

= 4.0.4 =
* ENHANCEMENT - display success message after copying Status Info.
* FIX - `Settings` page does not open or slow when there is big amount of attachments.
* FIX - in multisite network, removing custom tables properly when deleting site.
* FIX - skip setting ACL in Stateless mode and during Sync for the buckets with Uniform access, support WP_STATELESS_SKIP_ACL_SET constant [#712](https://github.com/udx/wp-stateless/issues/712).

= 4.0.3 =
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

= 4.0.2 =
* FIX - in multisite network, deleting site can potentially remove WP-Stateless tables from another site.
* COMPATIBILITY - Gravity Forms Compatibility updated for the newest Gravity Forms version.

= 4.0.1 =
* FIX - improvements to Data Optimization process.
* FIX - Data Optimization fixed for multisite environment.

= 4.0.0 =
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

= 3.4.1 =
* FIX - improve security while processing AJAX requests in Admin Panel

= 3.4.0 =
* ENHANCEMENT - removed `udx/lib-settings` package dependency for security reasons. 
* ENHANCEMENT - removed `udx/lib-utility` package dependency for security reasons.
* ENHANCEMENT - refactored `Settings` admin page to remove Angular dependency.
* ENHANCEMENT - including Software Bill of Materials (SBOM) to GitHub release.
* FIX - updated package dependencies for Google Client Library for security reasons.
* FIX - replaced `utf8_encode` with `mb_convert_encoding` to support PHP 8.2 and above [#678](https://github.com/udx/wp-stateless/issues/678).
* FIX - Fatal Error in `Stateless` mode if GCP access credentials are wrong [#693](https://github.com/udx/wp-stateless/issues/693).
* COMPATIBILITY - preventing PHP warnings while working with WooCommerce version 8.4.0 and above [696](https://github.com/udx/wp-stateless/issues/696).
* COMPATIBILITY - avoiding conflicts between builtin compatibilities and WP-Stateless Addon plugins.

= 3.3.0 =
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

= 3.2.5 =
* FIX - Folder setting does not allow custom structure [#608](https://github.com/udx/wp-stateless/issues/608).
* FIX - Stateless mode Incompatible with Inline Uploader [#675](https://github.com/udx/wp-stateless/issues/675).
* FIX - html tags incorrectly applied in notice [#680](https://github.com/udx/wp-stateless/issues/680).
* ENHANCEMENT - Add WP_STATELESS_SKIP_ACL_SET for skip ACL set for GCS [#625](https://github.com/udx/wp-stateless/issues/625).
* COMPATIBILITY - Add support for The Events Calendar [#599](https://github.com/udx/wp-stateless/issues/599).

= 3.2.4 =
* FIX - Website unresponsive after Upgrade [#669](https://github.com/udx/wp-stateless/issues/669).

= 3.2.3 =
* **WP-Stateless 3.2.3 requires PHP 8.0+. Recently, Google updated the official Google API PHP Client Library used by WP-Stateless to resolve security issues. This updated library requires PHP 8.0.**
* ENHANCEMENT - Updated Client library for Google APIs.
* ENHANCEMENT - Updated Monolog library to version 3.
* ENHANCEMENT - Updated JWT library.
* FIX - Fixed vulnerability issues.
* FIX - Fixed an errors and warnings on PHP 8.1.
* FIX - Fixed an error that occured when WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE is set.

= 3.2.2 =
* FIX -  Folder setting can't be saved from the settings page [#639](https://github.com/udx/wp-stateless/issues/639).

= 3.2.1 =
* FIX - Updated requirments.
* FIX - WP-Stateless 3.2.0 doesn’t upload docs, only images [#638](https://github.com/udx/wp-stateless/issues/638).

= 3.2.0 =
* **Before upgrading to WP-Stateless 3.2.0, please, make sure you tested it on your development environment. It may have breaking changes.**
* ENHANCEMENT - Upgraded `wpmetabox` library.
* ENHANCEMENT - Updated Client library for Google APIs.
* ENHANCEMENT - Updated Guzzle library to version 7.
* ENHANCEMENT - Updated JWT library.
* ENHANCEMENT - Updated `license` functionality, removed `update checker`.
* FIX - Fixed vulnerability issues.
* FIX - Fixed erros and warnings on PHP 8.
* FIX - problem after the upgrade [#628](https://github.com/udx/wp-stateless/issues/628).
* FIX - image_downsize() PHP8 Required parameter $id follows optional parameter $false [#619](https://github.com/udx/wp-stateless/issues/619).

= 3.1.1 =
* ENHANCEMENT - Notification for the administrator about finished synchronization. GitHub issue [#576](https://github.com/udx/wp-stateless/issues/576).
* FIX - Fixed an issue with PDF thumbnails. GitHub issue [#577](https://github.com/udx/wp-stateless/issues/577).
* FIX - Fixed an issue with synchronization in `Stateless` mode. GitHub issue [#575](https://github.com/udx/wp-stateless/issues/575).
* COMPATIBILITY - Changed the way compatibility files are stored on Multisite. GitHub issue [#588](https://github.com/udx/wp-stateless/issues/588).

= 3.1.0 =
* NEW - Completely rewritten the synchronization tool. GitHub issue [#523](https://github.com/udx/wp-stateless/issues/523).
* NEW - New configuration constant `WP_STATELESS_SYNC_MAX_BATCH_SIZE`. Sets the maximum size of a background sync batch of items to be saved in a single row in the database. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_max_batch_size).
* NEW - New configuration constant `WP_STATELESS_SYNC_LOG`. Sets a path to a log file where to output logging information during the background sync. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_log).
* NEW - New configuration constant `WP_STATELESS_SYNC_HEALTHCHECK_INTERVAL`. Defines an interval in minutes for a cron task that periodically checks the health of a particular background sync process. [More details](https://stateless.udx.io/docs/constants/#wp_stateless_sync_healthcheck_interval).
* FIX - Fixed an issue when original files were not deleted from the server in the Ephemeral mode. GitHub issue [#484](https://github.com/udx/wp-stateless/issues/484).
* FIX - Fixed an incorrect behavior of image `srcset` attribute in the Backup mode. GitHub issue [#558](https://github.com/udx/wp-stateless/issues/558).
* COMPATIBILITY - Litespeed Cache - Fixed an incorrect upload folder determination. GitHub issue [#527](https://github.com/udx/wp-stateless/issues/527).

= 3.0.4 =
* FIX - Fixed inability to use dashes in the upload folder name. GitHub issue [#565](https://github.com/udx/wp-stateless/issues/565).
* COMPATIBILITY - Elementor - Fixed wrong upload directory. GitHub issue [#560](https://github.com/udx/wp-stateless/issues/560).

= 3.0.3 =
* FIX - Fixed an incorrect file URL in Stateless mode on Edit Media screen. GitHub issue [#544](https://github.com/udx/wp-stateless/issues/544).

= 3.0.2 =
* FIX - Refactored the way files are being uploaded to GCS when `WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE` constant is defined. GitHub issue [#553](https://github.com/udx/wp-stateless/issues/553).
* FIX - Fixed the process of upgrading to 3.0 for multisite installations. GitHub issue [#549](https://github.com/udx/wp-stateless/issues/549).

= 3.0.1 =
* FIX - Fatal Error in Stateless mode. GitHub issue [#546](https://github.com/udx/wp-stateless/issues/546).

= 3.0 =
* **Before upgrading to WP-Stateless 3.0, please, make sure you tested it on your development environment. It may have breaking changes.**
* NEW - Setup assistant rewrite. GitHub issue [#477](https://github.com/udx/wp-stateless/issues/477).
* NEW - Recreate attachment metabox panel using metabox.io. GitHub issue [#470](https://github.com/udx/wp-stateless/issues/470).
* NEW - Updated the `Stateless` mode to not use local storage at all. Current `Stateless` mode setting mapped to new `Ephemeral` mode. GitHub issue [#482](https://github.com/udx/wp-stateless/issues/482).
* NEW - Files are now uploaded to GCS in chunks and chunk size will be determined based on free memory available. GitHub issue [#478](https://github.com/udx/wp-stateless/issues/478).
* NEW - File upload chunk size can be controlled with `WP_STATELESS_MEDIA_UPLOAD_CHUNK_SIZE` constant.  GitHub issue [#478](https://github.com/udx/wp-stateless/issues/478).
* FIX - Changed the default value for the Cache-Busting setting. GitHub issue [#361](https://github.com/udx/wp-stateless/issues/361).
* FIX - Fixed network override of Cache-Busting. GitHub issue [#468](https://github.com/udx/wp-stateless/issues/468).
* FIX - Fixed "Passing glue string after array is deprecated.". GitHub issue [#444](https://github.com/udx/wp-stateless/issues/444).
* FIX - Fixed Compatibility default value in multisite. GitHub issue [#464](https://github.com/udx/wp-stateless/issues/464).
* FIX - Fixed multisite wrong GCS path. GitHub issue [#407](https://github.com/udx/wp-stateless/issues/407).
* FIX - Don't check for Google Cloud Storage connectivity in stateless mode unless uploading. GitHub issue [#442](https://github.com/udx/wp-stateless/issues/442).
* COMPATIBILITY - Google App Engine - Added new compatibility support for Google App Engine. [#486](https://github.com/udx/wp-stateless/issues/486)
* COMPATIBILITY - Elementor - Fixed wrong MIME type for CSS files. GitHub issue [#395](https://github.com/udx/wp-stateless/issues/395).
* COMPATIBILITY - Polylang - Fixed missing metadata issue. GitHub issue [#378](https://github.com/udx/wp-stateless/issues/378).
* COMPATIBILITY - EWWW - Fixed mime type for WEBP images. GitHub issue [#371](https://github.com/udx/wp-stateless/issues/371).
* COMPATIBILITY - Simple Local Avatars - Added new compatibility support for Simple Local Avatars. GitHub issue [#297](https://github.com/udx/wp-stateless/issues/297).
* COMPATIBILITY - BuddyPress - Fixed BuddyPress compatibility. GitHub issue [#275](https://github.com/udx/wp-stateless/issues/275).
* COMPATIBILITY - Divi - Fixed Divi cache issue. GitHub issue [#430](https://github.com/udx/wp-stateless/issues/430).
* COMPATIBILITY - Gravity Forms - add compatibility for Gravity Forms Signature Add-On. [#501](https://github.com/udx/wp-stateless/issues/501).
* COMPATIBILITY - Litespeed - Fixed fatal error and warnings. [#491](https://github.com/udx/wp-stateless/issues/491).
* COMPATIBILITY - Imagify - Added support for webp. [#403](https://github.com/udx/wp-stateless/issues/403).
* ENHANCEMENT - Update Client library for Google APIs. [#446](https://github.com/udx/wp-stateless/issues/446).
* ENHANCEMENT - Wildcards for bucket folder settings. GitHub issue [#149](https://github.com/udx/wp-stateless/issues/149).
* ENHANCEMENT - Better CLI integration. GitHub issue [#447](https://github.com/udx/wp-stateless/issues/447), [#450](https://github.com/udx/wp-stateless/issues/450) and [#451](https://github.com/udx/wp-stateless/issues/451).
* ENHANCEMENT - Sync media according to new Bucket Folder settings. GitHub issue [#449](https://github.com/udx/wp-stateless/issues/449).
* ENHANCEMENT - Moved Bucket Folder setting in the File URL section. GitHub issue [#463](https://github.com/udx/wp-stateless/issues/463).
* ENHANCEMENT - Hide Regenerate and Sync with GCS when the mode is Disabled. GitHub issue [#440](https://github.com/udx/wp-stateless/issues/440).
* ENHANCEMENT - New endpoint for the Google Cloud Storage JSON API. GitHub issue [#384](https://github.com/udx/wp-stateless/issues/384).
* ENHANCEMENT - Renamed current `Stateless` mode to `Ephemeral`. GitHub issue [#481](https://github.com/udx/wp-stateless/issues/481).

= 2.3.2 =
* FIX - Fixed video file doesn't get deleted from the server in `Stateless` mode. GitHub issue [#418](https://github.com/udx/wp-stateless/issues/418).
* FIX - Fixed file size doesn't show under attachment details in `Stateless` mode. GitHub issue [#413](https://github.com/udx/wp-stateless/issues/413).
* FIX - Fixed Cache-Busting feature works even if the Mode is `Disabled`. GitHub issue [#405](https://github.com/udx/wp-stateless/issues/405).
* COMPATIBILITY - Fixed Gravity Form Post Image didn't include `Bucket Folder`. GitHub issue [#421](https://github.com/udx/wp-stateless/issues/421).
* COMPATIBILITY - Fixed Divi Builder Export. GitHub issue [#420](https://github.com/udx/wp-stateless/issues/420).
* COMPATIBILITY - Fixed BuddyBoss pages breaking after updating to 2.3.0. GitHub issue [#417](https://github.com/udx/wp-stateless/issues/417).

= 2.3.1 =
* Fix - Fixed fatal error, undefined function `is_wp_version_compatible`. GitHub issue [#414](https://github.com/udx/wp-stateless/issues/414).

= 2.3.0 =
* FIX - Fixed problem with WordPress 5.3. GitHub issue [#406](https://github.com/udx/wp-stateless/issues/406).
* FIX - Fixed problem with the Cache Busting feature. GitHub issue [#377](https://github.com/udx/wp-stateless/issues/377).
* COMPATIBILITY - Added compatibility support for WP Retina 2x pro. GitHub issue [#380](https://github.com/udx/wp-stateless/issues/380).
* COMPATIBILITY - Enhanced compatibility support for LiteSpeed Cache. GitHub issue [#365](https://github.com/udx/wp-stateless/issues/365).
* COMPATIBILITY - Enhanced compatibility support for ShortPixel Image Optimizer. GitHub issue [#364](https://github.com/udx/wp-stateless/issues/364), [#398](https://github.com/udx/wp-stateless/issues/398).
* COMPATIBILITY - Fixed Gravity Form export. GitHub issue [#408](https://github.com/udx/wp-stateless/issues/408).
* ENHANCEMENT - Improved upon add_media function for better compatibility support. GitHub issue [#382](https://github.com/udx/wp-stateless/issues/382).

= 2.2.7 =
* FIX - WP-Smush compatibility enhanced. GitHub Issue [#366](https://github.com/udx/wp-stateless/issues/366).
* FIX - Fixed multisite installation support. GitHub Issue [#370](https://github.com/udx/wp-stateless/issues/370).
* FIX - Fixed settings UI problems related to Cache-Busting option. GitHub Issue [#373](https://github.com/udx/wp-stateless/issues/373).
* FIX - Other minor fixes.

= 2.2.6 =
* FIX - Multisite Network Settings page fixed. GitHub Issue [#369](https://github.com/udx/wp-stateless/issues/369).
* FIX - Fixed incorrect Compatibilities behavior when Bucket Folder is set. GitHub Issue [#368](https://github.com/udx/wp-stateless/issues/368).
* FIX - Other minor fixes.

= 2.2.5 =
* NEW - Added ability to start sync process from specific Attachment ID. GitHub Issue [#360](https://github.com/udx/wp-stateless/issues/360).
* COMPATIBILITY - Added compatibility support for LiteSpeed Cache plugin. Especially to support optimized .webp images. GitHub Issue [#357](https://github.com/udx/wp-stateless/issues/357).
* FIX - Other minor fixes.

= Earlier versions =
Please refer to the separate changelog.txt file.
