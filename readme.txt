=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, maxim.peshkov, Anton Korotkoff, MariaKravchenko, alimuzzamanalim, smoot328
Donate link: https://www.usabilitydynamics.com
Tags: google, google cloud, google cloud storage, cdn, uploads, media, stateless, backup
License: GPLv2 or later
Requires PHP: 5.5
Requires at least: 4.0
Tested up to: 5.1
Stable tag: 2.2.7

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
= 2.2.6 =
* FIX - Multisite Network Settings page fixed. GitHub Issue [#369](https://github.com/wpCloud/wp-stateless/issues/369).

== Changelog ==

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

= 2.2.4 =
* NEW - Added new filter `wp_stateless_skip_add_media`. Allows skipping synchronization of the media object with GCS depending on custom condition. GitHub Issue [#344](https://github.com/wpCloud/wp-stateless/issues/344).
* FIX - Compatibility Manager is considering Child Themes now. GitHub Issue [#351](https://github.com/wpCloud/wp-stateless/issues/351).
* FIX - Custom domains handling has been fixed. GitHub Issue [#358](https://github.com/wpCloud/wp-stateless/issues/358).
* ENHANCEMENT - Imagify Image Optimizer and WP Smush compatibilities improved. GitHub Issue [#359](https://github.com/wpCloud/wp-stateless/issues/359).

= 2.2.3 =
* FIX - get_post_metadata does not break multi-dimensional arrays anymore. GitHub Issue [#352](https://github.com/wpCloud/wp-stateless/issues/352).
* FIX - PHP Warning: substr_compare() fixed. GitHub Issue [#350](https://github.com/wpCloud/wp-stateless/issues/350).
* FIX - Filtering Domain setting before saving in order to get rid of possible empty spaces. GitHub Issue [#348](https://github.com/wpCloud/wp-stateless/issues/348).
* FIX - Incorrect remote file path generated when disabled Organization setting. GitHub Issue [#343](https://github.com/wpCloud/wp-stateless/issues/343).
* FIX - Hiding admin notices correctly. GitHub Pull Request [#355](https://github.com/wpCloud/wp-stateless/pull/355).

= 2.2.2 =
* FIX - Proper 'srcset' attribute handling. GitHub Issue [#342](https://github.com/wpCloud/wp-stateless/issues/342).
* ENHANCEMENT - Minor fixes code quality.

= 2.2.1 =
* FIX - Security patch for Authenticated Remote Code Execution (RCE) vulnerability.

= 2.2.0 =
* FIX - Slow page generation when File URL Replacement is enabled. GitHub Issue [#265](https://github.com/wpCloud/wp-stateless/issues/265).
* FIX - Fatal error when WP Smush Pro compatibility is enabled. GitHub Issue [#325](https://github.com/wpCloud/wp-stateless/issues/325).
* FIX - Issue with Imagify. GitHub Issue [#326](https://github.com/wpCloud/wp-stateless/issues/326).
* FIX - Return correct srcset images. GitHub Issue [#328](https://github.com/wpCloud/wp-stateless/issues/328).
* FIX - Fatal error with GFForms. GitHub Issue [#330](https://github.com/wpCloud/wp-stateless/issues/330).
* FIX - Typo in admin notices. GitHub Issue [#337](https://github.com/wpCloud/wp-stateless/issues/337).
* ENHANCEMENT - Extended “File URL Replacement” options. GitHub Issue [#336](https://github.com/wpCloud/wp-stateless/issues/336).
* ENHANCEMENT - Service Account JSON is now hidden if set via constant. GitHub Issue [#320](https://github.com/wpCloud/wp-stateless/issues/320).
* ENHANCEMENT - New database table for tracking files not tracked in media library. GitHub Issue [#307](https://github.com/wpCloud/wp-stateless/issues/307).
* ENHANCEMENT - Updated depreciated function flagged by security software. GitHub Issue [#300](https://github.com/wpCloud/wp-stateless/issues/300).

= 2.1.9 =
* FIX - Resolved fatal error with OneCodeShop RML Amazon S3 plugin. GitHub Issue [#317](https://github.com/wpCloud/wp-stateless/issues/317).
* FIX - Resolved missing bucket in file URL when “storage.googleapis.com” was supplied in Domain field. GitHub Issue [#318](https://github.com/wpCloud/wp-stateless/issues/318).
* ENHANCEMENT - Support synchronization of files without metadata, such as .doc and .docx files. GitHub Issue [#316](https://github.com/wpCloud/wp-stateless/issues/316).

= 2.1.8 =
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

= 2.1.7 =
* ENHANCEMENT - Display dashboard-wide notice for existing users explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display notice when selecting stateless mode explaining stateless mode now enables cache-busting option.
* ENHANCEMENT - Display required message on cache-busting setting description when stateless mode is enabled.

= Earlier versions =
Please refer to the separate changelog.txt file.
