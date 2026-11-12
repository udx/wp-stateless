# WordPress Stateless Media Plugin

## Description

Upload and serve your WordPress media from Google Cloud Storage (GCS) with the WP-Stateless plugin. In as little as two minutes, you will be benefiting from serving your media from Google Cloud's distributed servers.

New to Google Cloud? Google is offering you a [$300 credit](https://console.cloud.google.com/freetrial?referralId=e1c28cf728ff49b38d4eb5add3f5bfc8) to get you started.

### Benefits
* Store and deliver media files on Google Cloud Storage instead of your server.
* Google Cloud Storage is geo-redundant, meaning your media is delivered by the closest server - reducing latency and improving page speed.
* Scale your WordPress website across multiple servers without the need for synchronizing media files.
* Native integration between Google Cloud Storage and WordPress.
* $300 free trial from Google Cloud. Nice!

### Modes
* Backup - Upload media files to Google Storage and serve local file URLs.
* CDN - Copy media files to Google Storage and serve them directly from there.
* Ephemeral - Store and serve media files with Google Cloud Storage only. Media files are not stored locally, but local storage is used temporarily for processing and is required for certain compatibilities.
* Stateless - Store and serve media files with Google Cloud Storage only. Media files are not stored locally.

### Features
* Stateless Connect: sign in with Google from wp-admin and storage is set up in your own Google Cloud account.
* No need to manually create service accounts or buckets - handled automatically.
* Settings panel provides you with further GCS configuration and file URL customization.
* Mask the default GCS URL with your own custom domain.
* Automatically replace hardcoded media URLs with GCS equivalents in post editor and meta.
* Batch image thumbnail regeneration.
* Synchronization tools for uploading existing files and images.
* All settings supported with wp-config constants and network setting overrides.
* Multiple modes: Backup, CDN, Ephemeral, Stateless.
* All files served in HTTPS mode.
* Serverless platform compatible, including Google App Engine.
* Multisite compatible.

### Addons
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
* [BuddyPress Addon](https://wordpress.org/plugin/wp-stateless-buddypress-addon/)
* [BuddyBoss Platform Addon](https://wordpress.org/plugins/wp-stateless-buddyboss-platform-addon/)
* [Polylang Pro Addon](https://wordpress.org/plugins/wp-stateless-polylang-pro-addon/)
* [Simple Local Avatars](https://wordpress.org/plugins/wp-stateless-simple-local-avatars-addon/)

### Support, Feedback, & Contribute
We welcome community involvement via the [GitHub repository](https://github.com/udx/wp-stateless).

### Custom Development
Looking for a unique feature for your next project? [Hire us!](https://udx.io/)

## Installation

1. Search, install, and activate the *WP-Stateless* plugin via your WordPress dashboard. Unconfigured sites open Stateless Connect automatically; you can also open *Media > Stateless Connect*.
2. Click *Sign in with Google* and confirm the connection for your site on stateless.ci. You return to the same tab, or can use the eight character pairing code on another device.
3. Choose *Create my storage* for the recommended project, bucket and CDN setup, or open *Advanced storage options* to choose an existing project or bucket, another location or another mode. WP-Stateless creates a service account with access to that bucket only, and Google sends its key directly to your site.
4. Upload a test image to confirm your storage is working. Visit *Media > Stateless Settings* for more options.

For a more detailed installation and setup walkthrough, please see the [setup guide](https://stateless.ci/setup/) and the [manual setup instructions](https://stateless.ci/docs/manual-setup/).

## Frequently Asked Questions

### What are the minimum server requirements for this plugin?

Beyond the [official WordPress minimum requirements](https://codex.wordpress.org/Template:Server_requirements), WP-Stateless requires a minimum PHP version of 8.1 or higher and OpenSSL to be enabled.

### What wp-config constants are supported?

For a complete list of supported wp-config constants, please consult the [configuration constants reference](https://stateless.ci/docs/constants/).

### How do I manually generate the Service Account JSON?

Stateless Connect creates the Service Account JSON automatically for you, but you can follow these steps if you choose to create it manually.

1. Visit Google Cloud Console, and go to *IAM & Admin > Service accounts*.
2. Click *Create Service Account* and name it *wp-stateless*.
3. Set the role to *Storage > Storage Admin*.
4. Check *Furnish a new private key* and select *JSON* as the key type.
5. Open the JSON file and copy the contents into the *Service Account JSON* textarea within the WP-Stateless settings panel.

### Where can I submit feature requests or bug reports?

We encourage community feedback and discussion through issues on the [GitHub repository](https://github.com/udx/wp-stateless/issues).

### Can I test new features before they are released?

To ensure new releases cause as little disruption as possible, we rely on a number of early adopters who assist us by testing out new features before they are released. [Please contact us](https://udx.io/) if you are interested in becoming an early adopter.

### Who maintains this plugin?

[UDX](https://udx.io/) maintains this plugin by continuing development through its own staff, reviewing pull requests, testing, and steering the overall release schedule. UDX is located in Durham, North Carolina, and provides WordPress engineering and hosting services to clients throughout the United States.

## External services

WP-Stateless stores and serves your media from Google Cloud Storage in your own Google account. It relies on the services below. Your media files are sent only to Google Cloud Storage.

### Google Cloud
The plugin uploads, reads and deletes media in the Google Cloud Storage bucket you configure, using your own service account. During Stateless Connect it also uses Google sign-in and Google Cloud APIs (projects, billing, IAM, service usage and storage) with the permissions you approve, to create or select the project, bucket and service account. This service is provided by Google: [Terms of Service](https://cloud.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).

### stateless.ci (Stateless Connect)
Stateless Connect uses stateless.ci, operated by UDX, to handle Google sign-in for the plugin. It is contacted when an administrator opens the Stateless Connect screen, which also opens once after the plugin is activated on a site that has no storage configured. Opening the screen sends the site's WordPress admin return address and a one-time challenge so that a sign-in can be prepared. Signing in passes the Google authorization through stateless.ci for one-time delivery to your site, and later token refreshes also pass through it. The service account key is created in your own Google Cloud project and never passes through stateless.ci.

To learn whether setup works, the plugin also reports setup progress for that sign-in attempt: screen viewed, sign-in started, storage created or failed with a general reason, and whether the first upload succeeded. These records do not contain the site address, email address, project ID or bucket name, and they expire after seven days.

Sites configured by hand with a bucket and service account JSON under Stateless Settings do not need Stateless Connect, and the `WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT` constant keeps the screen from opening. This service is provided by UDX: [Privacy Policy](https://stateless.ci/privacy-policy/).

### UDX product check-in
While you use the WordPress admin, the plugin contacts UDX (usabilitydynamics.com) at most once a day to check for product notices. The request includes your site address, the plugin version and the UDX products detected on the site. This service is provided by UDX: [Privacy Policy](https://udx.io/privacy-policy).
