=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, maxim.peshkov, Anton Korotkoff, MariaKravchenko, alimuzzamanalim, smoot328
Donate link: https://www.usabilitydynamics.com
Tags: google, google cloud, google cloud storage, cdn, uploads, media, stateless, backup
License: GPLv2 or later
Requires at least: 4.0
Tested up to: 4.9.4
Stable tag: 2.1.4

Upload and serve your WordPress media files from Google Cloud Storage.

== Description ==

Upload and serve your WordPress media from Google Cloud Storage (GCS) with the WP-Stateless plugin. In as little as two minutes, you will be benefitting from serving your media from Google Cloud's distributed servers.

New to Google Cloud? Google is offering you a [$300 credit](https://cloud.google.com/free/) to get you started.

= Benefits =
* Store and deliver media files on Google Cloud Storage instead of your server.
* Google Cloud Storage is geo-redundant, meaning your media is delivered by the closest server - reducing latency and improving page speed.
* Scale your WordPress website across multiple servers without the need of synchronizing media files.
* Native integration between Google Cloud Storage and WordPress.
* $300 free trial from Google Cloud. Nice!

= Modes =
* Backup - Upload media files to Google Storage and serve local file urls.
* CDN - Copy media files to Google Storage and serve them directly from there.
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
* Multiple modes: Backup, CDN, Stateless.
* All files served in HTTPS mode.
* Multisite compatible.

= Support, Feedback, & Contribute =
We welcome community involvement via the [GitHub repository](https://github.com/wpCloud/wp-stateless).

= Custom Development =
Looking for a unique feature for your next project? [Hire us!](https://www.usabilitydynamics.com/contact)

== Installation ==

1. Search, install, and activate the *WP-Stateless* plugin via your WordPress dashboard.
2. Begin WP-Stateless setup assistant at *Media > Stateless Setup* and click "Get Started Now."
3. Click "Google Login" and sign-in with your Google account.
4. Set a Google Cloud Project, Google Cloud Storage Bucket, and Google Cloud Billing Account and click "Continue."
5. Installation and setup is now complete. Visit *Media > Stateless Settings* for more options.

== Screenshots ==

1. Settings Panel: Supports network setting and wp-config constant overrides.
2. Setup Assistant 
3. Setup Assistant: Google Login
4. Setup Assistant: Approve Permissions
5. Setup Assistant: Project & Bucket
6. Setup Assistant: Complete
7. Edit Media: Image stored on Google Cloud Storage.

== Frequently Asked Questions ==

= What wp-config constants are supported? =

For a complete list of supported wp-config constants, please consult the [GitHub wiki](https://github.com/wpCloud/wp-stateless/wiki/Constants).

= How do I manually generate the Service Account JSON? =

The WP-Stateless setup assistant will create the Service Account JSON automatically for you, but you can follow these steps if you choose to create it manually.

1. Visit Google Cloud Console, and go to *IAM & Admin > Service accounts*.
2. Click *Create Service Account* and name it *wp-stateless*.
3. Set the role to *Storage > Storage Admin*.
4. Check *Furnish a new private key* and select *JSON* as the key type.
5. Open the JSON file and copy the contents into the *Service Account JSON* textarea within the WP-Stateless settings panel.

= Where can I submit feature requests or bug reports? =

We encourage community feedback and discussion through issues on the [GitHub repository](https://github.com/wpCloud/wp-stateless/issues).

= Can I test new features before they are released? =

To ensure new releases cause as little disruption as possible, we rely on a number of early adopters who assist us by testing out new features before they are released. [Please contact us](https://www.usabilitydynamics.com/contact) if you are interested in becoming an early adopter.

= Who maintains this plugin? =

[Usability Dynamics](https://www.usabilitydynamics.com/) maintains this plugin by continuing development through it's own staff, reviewing pull requests, testing, and steering the overall release schedule. Usability Dynamics is located in Durham, North Carolina and provides WordPress engineering and hosting services to clients throughout the United States.

== Changelog ==

= 2.1.4 =
* ENHANCEMENT - Updated Google OAuth URL for Setup Assistant.

= 2.1.3 =
* ENHANCEMENT - Updates to text explainers in Setup Assistant.
* ENHANCEMENT - Refined redirection logic when activating plugin.
* FIX - Removed extra space in converted URLs.

= 2.1.2 =
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

= 2.1.1 =
* FIX - Fixed double slash when Organization is disabled.
* FIX - Fatal error with GuzzleHttp.
* FIX - Fixed content-type assignment.
* ENHANCEMENT - Added support for https URLs in Domain field.
* COMPATIBILITY - Advanced Custom Fields Image Crop Addon.

= 2.1.0 =
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

= 2.0.3 =
* FIX - Fixed Fatal Error which was occurring on WordPress Multisite after upgrading plugin from 1.x to 2.x.
* ENHANCEMENT - Improved support of PDF files.

= 2.0.2 =
* FIX - Fixed Fatal Error which were caused by using PHP 5.4 and less.
* FIX - Fixed Fatal Error which was caused on Media page when WP Smush Pro plugin is activated.
* FIX - Fixed detection of plugin files paths. The issue was occurring on installations with custom file structures ( e.g. Bedrock platform ).
* FIX - Fixed redirection URL to Setup Wizard on plugin activation.
* ENHANCEMENT - Updated the minimum requirements for PHP to 5.5 to prevent fatal errors and possible warnings.

= 2.0.1 =
* ENHANCEMENT - Added compatibility with Google SDK v1.x version to prevent conflicts with third-party plugins.
* ENHANCEMENT - Added warning message if old Google SDK version is loaded by third-party plugin.

= 2.0.0 =
* NEW - Added stateless mode.
* NEW - Dedicated settings panel.
* NEW - Setup assistant for initial plugin activation.
* NEW - Support for replacing default GCS domain with custom domain.
* ENHANCEMENT - Expanded network setting overrides.
* ENHANCEMENT - Expanded wp-config constants.
* ENHANCEMENT - Relocated synchronization and regeneration tools to new settings panel.

= 1.9.2 =
* ENHANCEMENT - Added ability to modify default bucket link via 'wp_stateless_bucket_link' filter.
* ENHANCEMENT - Added checking of connection to GCS once per four hours instead of doing it on every page load.
* ENHANCEMENT - Google SDK was moved from vendor dir. So it's not loaded on every page load anymore, but only when it's required.
* ENHANCEMENT - Updated Composer Autoload logic.
* ENHANCEMENT - Reverted all changes included to 1.9.1 version because of conflicts.

= 1.9.0 =
* NEW - Added new ability to define cacheControl for remote objects.
* NEW - Added new option that adds random hashes to file names.

= 1.8.0 =
* FIX - Fixed the issue on multisite setup (switch_to_blog now works as expected).
* FIX - Performance fixes.
* NEW - Added the ability to regenerate and synchronize separate Media file from the list.
* NEW - Added the ability to regenerate and synchronize Media file from edit screen.
* ENHANCEMENT - UI cleanup.

= 1.7.3 =
* Initial public release.
