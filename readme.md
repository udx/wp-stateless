### WordPress Stateless Media Plugin

***
[![Issues - Bug](https://badge.waffle.io/wpCloud/wp-stateless.png?label=bug&title=Bugs)](http://waffle.io/wpCloud/wp-stateless)
[![Issues - Backlog](https://badge.waffle.io/wpCloud/wp-stateless.png?label=backlog&title=Backlog)](http://waffle.io/wpCloud/wp-stateless/)
[![Issues - Active](https://badge.waffle.io/wpCloud/wp-stateless.png?label=in%20progress&title=Active)](http://waffle.io/wpCloud/wp-stateless/)
***
[![Scrutinizer Quality](http://img.shields.io/scrutinizer/g/wpCloud/wp-stateless.svg)](https://scrutinizer-ci.com/g/wpCloud/wp-stateless)
***

The WP-Stateless plugin copies your media uploads to Google Cloud Storage in real-time as you add items to your Media Library. The uploaded files are then served directly from the Google bucket, making your media files load quicker from the distributed Google servers. The plugin will handle all media uploads including image thumbnails, PDF documents, audio files, and more.

This plugin is useful for running multiple environments or instances of your WordPress site.

Plugin requires PHP 5.4 or higher.

See the plugin on [WordPress.org](https://wordpress.org/plugins/wp-stateless/)

Tutorial videos:
* [Overview](https://www.youtube.com/watch?v=aGntFnKwkE0)
* [Setting Up](https://www.youtube.com/watch?v=szf5hTns4Ak)

### Features

* Run batch synchronization that will copy all files to Google Cloud Storage bucket.
* Run batch image regeneration that will update thumbnail sizes and copy them to Google Cloud Storage bucket.
* Automatically replace hardcoded URLs found in content with URLs using the Google Cloud Storage url.
* All files are served in HTTPS mode.
* Support for MultiSite configuration.
* Configuration of plugin via constants from the `wp-config.php` file.
* Stores backup of uploaded media files on your server as well.

### Usage

```php
define( 'WP_STATELESS_MEDIA_BUCKET', 'media.application-domain.com' );
define( 'WP_STATELESS_MEDIA_MODE', 'cdn' );
define( 'WP_STATELESS_MEDIA_KEY_FILE_PATH', '/var/www/wp-content/keys/application-name-service-id.p12' );
define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT', '12345689-hash@developer.gserviceaccount.com' );
```

```json
{
  "wp_stateless_media": {
    "bucket": "media.application-domain.com",
    "mode": "cdn",
    "key_file_path": "/var/www/wp-content/keys/application-name-service-id.p12",
    "service_account": "12345689-hash@developer.gserviceaccount.com"
  }
}
```

Set options:

```bash
wp option update sm_mode cdn
wp option update sm_service_account_name blah@google.com
wp option update sm_key_file_path /var/www/wp-content/keys/some-key.p12
wp option update sm_bucket media.site.com
```

### Available Constants

Setting a setting via constants will prevent ability to make changes in control panel.

* `WP_STATELESS_MEDIA_MODE` - Set to "disabled", "backup" or "cdn" to configure mode. 
* `WP_STATELESS_MEDIA_SERVICE_ACCOUNT` - Google email address of service account.
* `WP_STATELESS_MEDIA_KEY_FILE_PATH` - Absolute, or relative to web-root, path to P12 file.

### Available WordPress Actions

* `wp_stateless_bucket_link` - Filter, which allows to modify default bucket link `https://storage.googleapis.com/` to custom one.

### Available Environment Variables
Setting a setting via environment variables will prevent ability to make changes in control panel.

* GOOGLE_APPLICATION_CREDENTIALS - Absolute, or relative to web-root, path to JSON key file.

### Response Headers

* `x-goog-meta-object-id`
* `x-goog-meta-height`
* `x-goog-meta-width`
* `x-goog-meta-source-id`
* `x-goog-meta-file-hash`
* `x-goog-meta-child-of`
