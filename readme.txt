=== WP-Stateless - Google Cloud Media Storage ===
Contributors: usability_dynamics, andypotanin, maxim.peshkov, Anton Korotkoff, ideric, MariaKravchenko, flixos90
Donate link: https://www.usabilitydynamics.com
Tags: admin, google, bucket, CDN, google cloud storage, media, mirror, uploads, stateless
License: GPLv2 or later
Requires at least: 4.0
Tested up to: 4.5.3
Stable tag: 1.9.1

== Description ==

The WP-Stateless plugin copies your media uploads to Google Cloud Storage in real-time as you add items to your Media Library. The uploaded files are then served directly from the Google bucket, making your media files load quicker from the distributed Google servers. The plugin will handle all media uploads including image thumbnails, PDF documents, audio files, and more.

This plugin is useful for running multiple environments or instances of your WordPress site.

Plugin requires PHP 5.4 or higher.

Overview
https://www.youtube.com/watch?v=aGntFnKwkE0

Setting Up
https://www.youtube.com/watch?v=szf5hTns4Ak

> See the plugin on [GitHub](https://github.com/wpCloud/wp-stateless)

= Features =
* Run batch synchronization that will copy all files to Google Cloud Storage bucket.
* Run batch image regeneration that will update thumbnail sizes and copy them to Google Cloud Storage bucket.
* Automatically replace hardcoded URLs found in content with URLs using the Google Cloud Storage url.
* All files are served in HTTPS mode.
* Support for MultiSite configuration.
* Configuration of plugin via constants from the wp-config.php file.
* Stores backup of uploaded media files on your server as well.

== Installation ==

1. Activate the WP-Stateless plugin and navigate to the Settings > Media page to review needed options.
2. Open Google Cloud Storage settings page (https://console.cloud.google.com/storage/) and create a new bucket.
3. Enter the bucket name you specified in previous step into Settings > Media > "Bucket".
4. Open Google Cloud Storage Permissions page (https://console.cloud.google.com/permissions/serviceaccounts) and create new Service Account.
5. Copy and paste the JSON key provided to you by the previous step into Settings > Media > "Service Account JSON" textarea.
6. On the Media > Settings page enable the "CDN" mode to enable the plugin

== Screenshots ==

1. Create a bucket on Google Cloud Storage
2. Create a Service Account Key
3. Use JSON key type
4. Copy and paste JSON into WP-Stateless settings
5. Tools > Stateless Sync
6. Resized Images Appear in Bucket
7. The synchronized images now use GCS url

== Frequently Asked Questions ==

= How to configure the plugin? =
See Installation tab.

[For any kind of support just contact us by this link](https://www.usabilitydynamics.com/contact-us)

== Upgrade Notice ==

= 1.7.3 =
* Initial public release.

== Changelog ==

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
