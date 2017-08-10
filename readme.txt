=== WP-Stateless - Google Cloud Storage ===
Contributors: usability_dynamics, andypotanin, ideric, maxim.peshkov, Anton Korotkoff, MariaKravchenko, alimuzzamanalim
Donate link: https://www.usabilitydynamics.com
Tags: google cloud, google cloud storage, cdn, uploads, media, stateless, backup
License: GPLv2 or later
Requires at least: 4.0
Tested up to: 4.8.1
Stable tag: 2.0.0

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

1. Create a bucket on Google Cloud Storage
2. Create a Service Account Key
3. Use JSON key type
4. Copy and paste JSON into WP-Stateless settings
5. Tools > Stateless Sync
6. Resized Images Appear in Bucket
7. The synchronized images now use GCS url

== Changelog ==

= 2.0.0 =
* NEW - Added stateless mode.
* NEW - Dedicated settings panel.
* NEW - Setup assistant for initial plugin activation.
* NEW - Support for replacing default GCS domain with custom domain.
* ENHANCEMENT - Expanded network setting overrides.
* ENHANCEMENT - Expanded wp-config constants.
* ENHANCEMENT - Relocated synchronization and regeneration tools to new settings panel.

= 1.9.1 =
* Extended Network Settings.

= 1.9.0 =
* Added new ability to define cacheControl for remote objects.
* Added new option that adds random hashes to file names.

= 1.8.0 =
* Added the ability to regenerate and synchronize separate Media file from the list.
* Added the ability to regenerate and synchronize Media file from edit screen.
* Fixed the issue on multisite setup (switch_to_blog now works as expected).
* Performance fixes.
* UI cleanup.

= 1.7.3 =
* Initial public release.