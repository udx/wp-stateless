#### 1.8.0
* https://github.com/wpCloud/wp-stateless/issues/31

#### 1.7.3
* Added ability to fix previously failed items.

#### 1.7.1
* Migrated from p12 to JSON.
* New feature of media sync.
* New option Root Directory.
* Optimized uploading process.
* Other options.

#### 1.7.0
* Fixed conflict with SSL forcing plugin.

#### 1.6.0
* Plugin Updates ability added.
* WordPress 4.4 compatibility fixed.
* Post content filter fixed.

#### 1.5.0
* Migration into wp-stateless from wp-stateless-media.
* Version bump to resolve bucket permissions issues.
 
#### 1.3.2
* Fixed issue with wp_normalize_path causing a fatal error on older installs.
* Removed $_SESSION usage for transients.

#### 1.3.1
* Added ability to upload non-images to bucket.
* Fixed issue with running batches in WP CLI

#### 1.3.0
* Added WP CLI functionality.
* Added WP CLI command to move all legacy meta data to serialized array.
* Changed the way of storing SM cloud meta data.

#### 1.2.0
* Added Imagemagic/GD check to warn admin that thumbnails will not be generated.
* Added mediaLink, mediaLink and id storage for uploaded objects.
* Added support for cacheControl with default settings based on Mime type of upload.
* Added sm:item:cacheControl and sm:item:contentDisposition filters.
* Cleaned-up metadata that is made available to GCS and visible in response headers.
* Removed app_name, using blog domain name automatically.
* Added Cache Control to media editor.

#### 1.1.0
* Added support for <code>WP_STATELESS_MEDIA_MODE</code>.
* Renamed constants <code>STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>STATELESS_MEDIA_KEY_FILE_PATH</code> to <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.2
* Added a *view* link to media edit page for synchronized items.
* Added some *wp_get_attachment_image* dynamic attributes.

#### 1.0.1
* Added Network Management option.
* Added support for <code>WP_STATELESS_MEDIA_SERVICE_ACCOUNT_NAME</code> and <code>WP_STATELESS_MEDIA_KEY_FILE_PATH</code>.

#### 1.0.0
* Set branch to v1.0.
* Removed autocompletion from Email Address and Application Name fields.
* Moved lib/classes into just lib.
* Rename class-Bootstrap.php to be lowercased.
* Added composer/installers to composer.json 'required' dependencies.
* Rename stateless-media.php to wp-stateless-media.php so there aren't unexpected plugin basename issues.
* Change package name to wp-stateless-media in package.json
* Rename 'wpCloud/wp-stateless-media' to 'wpcloud/wp-stateless-media' in composer.json, Composer does not allow uppercase names.

#### 0.2.1
* Added 'Settings' link to plugin list.
