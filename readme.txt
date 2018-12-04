=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, maxim.peshkov, Anton Korotkoff, MariaKravchenko, alimuzzamanalim, smoot328
Donate link: https://www.usabilitydynamics.com
Tags: google, google cloud, google cloud storage, cdn, uploads, media, stateless, backup
License: GPLv2 or later
Requires PHP: 5.5
Requires at least: 4.0
Tested up to: 4.9.8
Stable tag: 2.1.9

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
For a more detailed installation and setup walkthrough, please see the [manual setup instructions on Github](https://github.com/wpCloud/wp-stateless/wiki/Manual-Setup).

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

Beyond the [official WordPress minimum requirements](https://codex.wordpress.org/Template:Server_requirements), WP-Stateless requires a minimum PHP version of 5.5 or higher and OpenSSL to be enabled.

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


== Upgrade Notice ==
= 2.1.9 =
* FIX - Resolved fatal error with OneCodeShop RML Amazon S3 plugin. GitHub Issue [#317](https://github.com/wpCloud/wp-stateless/issues/317).
* FIX - Resolved missing bucket in file URL when “storage.googleapis.com” was supplied in Domain field. GitHub Issue [318](https://github.com/wpCloud/wp-stateless/issues/318).
* ENHANCEMENT - Support synchronization of files without metadata, such as .doc and .docx files. GitHub Issue [316](https://github.com/wpCloud/wp-stateless/issues/316).

== Changelog ==
= 2.1.9 =
* FIX - Resolved fatal error with OneCodeShop RML Amazon S3 plugin. GitHub Issue [#317](https://github.com/wpCloud/wp-stateless/issues/317).
* FIX - Resolved missing bucket in file URL when “storage.googleapis.com” was supplied in Domain field. GitHub Issue [318](https://github.com/wpCloud/wp-stateless/issues/318).
* ENHANCEMENT - Support synchronization of files without metadata, such as .doc and .docx files. GitHub Issue [316](https://github.com/wpCloud/wp-stateless/issues/316).

= 2.1.8 =
* FIX - WooCommerce product export.
* FIX - PDF previews in media library now supported.
* ENHANCEMENT - Improved error message when there is nothing to sync.
* ENHANCEMENT - Renamed constant WP_STATELESS_MEDIA_CACHE_CONTROL to WP_STATELESS_MEDIA_CACHE_BUSTING.
* ENHANCEMENT - Domain field functionality now allows webmaster to control http or https
* ENHANCEMENT - Notice about Stateless mode requiring the Cache-Busting option is displayed to those using Stateless mode.
* ENHANCEMENT - Upload full size image before generating thumbnails.
* COMPATIBILITY - Added compatibility support for Learndash plugin.
* COMPATIBILITY - Added compatibility support for BuddyPress plugin.
* COMPATIBILITY - Added compatibility support for Divi Builder export.
* COMPATIBILITY - Added compatibility support for Elementor plugin.

= 2.1.7 =
* ENHANCEMENT - Display dashboard-wide notice for existing users explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display notice when selecting stateless mode explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display required message on cache-busting setting description when stateless mode is enabled.

= 2.1.6 =
* FIX - Resolved Google SDK conflict.
* FIX - ICompatibility.php errors notice.
* FIX - Undefined index: gs_link in class-bootstrap.php.
* FIX - Media files with accent characters would not upload correctly to the bucket. 
* ENHANCEMENT - Force Cache-Busting when using Stateless mode. 
* ENHANCEMENT - New admin notice design.
* ENHANCEMENT - Improved and clear error message. 
* ENHANCEMENT - Renamed constant WP_STATELESS_MEDIA_ON_FLY to WP_STATELESS_DYNAMIC_IMAGE_SUPPORT. 
* ENHANCEMENT - Update Google Libraries.
* ENHANCEMENT - Renamed constant WP_STATELESS_MEDIA_HASH_FILENAME to WP_STATELESS_MEDIA_CACHE_BUSTING.
* COMPATIBILITY - Renamed constant WP_STATELESS_COMPATIBILITY_WPSmush to WP_STATELESS_COMPATIBILITY_WPSMUSH.
* COMPATIBILITY - Added support for WooCommerce Extra Product Options.
* COMPATIBILITY - Added support for WPForms Pro.
* COMPATIBILITY - Improved ShortPixel compatibility.
* COMPATIBILITY - Fixed ACF Image Crop compatibility.

= 2.1.5 =
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

= 2.1.4 =
* ENHANCEMENT - Updated Google OAuth URL for Setup Assistant.

= Earlier versions =
Please refer to the separate changelog.txt file.
