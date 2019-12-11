=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, maxim.peshkov, Anton Korotkoff, MariaKravchenko, alimuzzamanalim, smoot328
Donate link: https://www.usabilitydynamics.com
Tags: google, google cloud, google cloud storage, cdn, uploads, media, stateless, backup
License: GPLv2 or later
Requires PHP: 5.5
Requires at least: 4.7.0
Tested up to: 5.3
Stable tag: 2.3.2

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
For a more detailed installation and setup walkthrough, please see the [manual setup instructions on Github](https://wp-stateless.github.io/docs/manual-setup/).

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

For a complete list of supported wp-config constants, please consult the [GitHub documentation](https://wp-stateless.github.io/docs/constants/).

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
Fixed fatal error, undefined function `is_wp_version_compatible`.

== Changelog ==
= 2.3.2 =
* FIX - Fixed video file doesn't get deleted from the server in `Stateless` mode. GitHub issue [#418](https://github.com/wpCloud/wp-stateless/issues/418).
* FIX - Fixed file size doesn't show under attachment details in `Stateless` mode. GitHub issue [#413](https://github.com/wpCloud/wp-stateless/issues/413).
* FIX - Fixed Cache-Busting feature works even if the Mode is `Disabled`. GitHub issue [#405](https://github.com/wpCloud/wp-stateless/issues/405).
* COMPATIBILITY - Fixed Gravity Form Post Image didn't include `Bucket Folder`. GitHub issue [#421](https://github.com/wpCloud/wp-stateless/issues/421).
* COMPATIBILITY - Fixed Divi Builder Export. GitHub issue [#420](https://github.com/wpCloud/wp-stateless/issues/420).
* COMPATIBILITY - Fixed BuddyBoss pages breaking after updating to 2.3.0. GitHub issue [#417](https://github.com/wpCloud/wp-stateless/issues/417).

= 2.3.1 =
* Fix - Fixed fatal error, undefined function `is_wp_version_compatible`. GitHub issue [#414](https://github.com/wpCloud/wp-stateless/issues/414).

= 2.3.0 =
* FIX - Fixed problem with WordPress 5.3. GitHub issue [#406](https://github.com/wpCloud/wp-stateless/issues/406).
* FIX - Fixed problem with the Cache Busting feature. GitHub issue [#377](https://github.com/wpCloud/wp-stateless/issues/377).
* COMPATIBILITY - Added compatibility support for WP Retina 2x pro. GitHub issue [#380](https://github.com/wpCloud/wp-stateless/issues/380).
* COMPATIBILITY - Enhanced compatibility support for LiteSpeed Cache. GitHub issue [#365](https://github.com/wpCloud/wp-stateless/issues/365).
* COMPATIBILITY - Enhanced compatibility support for ShortPixel Image Optimizer. GitHub issue [#364](https://github.com/wpCloud/wp-stateless/issues/364), [#398](https://github.com/wpCloud/wp-stateless/issues/398).
* COMPATIBILITY - Fixed Gravity Form export. GitHub issue [#408](https://github.com/wpCloud/wp-stateless/issues/408).
* ENHANCEMENT - Improved upon add_media function for better compatibility support. GitHub issue [#382](https://github.com/wpCloud/wp-stateless/issues/382).

= 2.2.7 =
* FIX - WP-Smush compatibility enhanced. GitHub Issue [#366](https://github.com/wpCloud/wp-stateless/issues/366).
* FIX - Fixed multisite installation support. GitHub Issue [#370](https://github.com/wpCloud/wp-stateless/issues/370).
* FIX - Fixed settings UI problems related to Cache-Busting option. GitHub Issue [#373](https://github.com/wpCloud/wp-stateless/issues/373).
* FIX - Other minor fixes.

= 2.2.6 =
* FIX - Multisite Network Settings page fixed. GitHub Issue [#369](https://github.com/wpCloud/wp-stateless/issues/369).
* FIX - Fixed incorrect Compatibilities behavior when Bucket Folder is set. GitHub Issue [#368](https://github.com/wpCloud/wp-stateless/issues/368).
* FIX - Other minor fixes.

= 2.2.5 =
* NEW - Added ability to start sync process from specific Attachment ID. GitHub Issue [#360](https://github.com/wpCloud/wp-stateless/issues/360).
* COMPATIBILITY - Added compatibility support for LiteSpeed Cache plugin. Especially to support optimized .webp images. GitHub Issue [#357](https://github.com/wpCloud/wp-stateless/issues/357).
* FIX - Other minor fixes.

= Earlier versions =
Please refer to the separate changelog.txt file.
