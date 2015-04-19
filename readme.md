### Stateless Media Plugin

wpCloud Stateless Media for GCE

***
[![Issues - Bug](https://badge.waffle.io/wpCloud/wp-stateless.png?label=bug&title=Bugs)](http://waffle.io/wpCloud/wp-stateless)
[![Issues - Backlog](https://badge.waffle.io/wpCloud/wp-stateless.png?label=backlog&title=Backlog)](http://waffle.io/wpCloud/wp-stateless/)
[![Issues - Active](https://badge.waffle.io/wpCloud/wp-stateless.png?label=in progress&title=Active)](http://waffle.io/wpCloud/wp-stateless/)
***
[![Dependency Status](https://gemnasium.com/wpCloud/wp-stateless.svg)](https://gemnasium.com/wpCloud/wp-stateless)
[![Scrutinizer Quality](http://img.shields.io/scrutinizer/g/wpCloud/wp-stateless.svg)](https://scrutinizer-ci.com/g/wpCloud/wp-stateless)
[![Scrutinizer Coverage](http://img.shields.io/scrutinizer/coverage/g/wpCloud/wp-stateless.svg)](https://scrutinizer-ci.com/g/wpCloud/wp-stateless)
[![Packagist Vesion](http://img.shields.io/packagist/v/wpCloud/wp-stateless.svg)](https://packagist.org/packages/wpCloud/wp-stateless)
[![CircleCI](https://circleci.com/gh/wpCloud/wp-stateless.png)](https://circleci.com/gh/wpCloud/wp-stateless)
***


### Available Constants
Setting a setting via constants will prevent ability to make changes in control panel.

* WP_STATELESS_MEDIA_MODE - Set to "disabled", "backup" or "cdn" to configure mode. 
* WP_STATELESS_MEDIA_SERVICE_ACCOUNT - Google email address of service account.
* WP_STATELESS_MEDIA_KEY_FILE_PATH - Absolute, or relative to web-root, path to P12 file.

### Response Headers

* x-goog-meta-object-id
* x-goog-meta-height
* x-goog-meta-width
* x-goog-meta-source-id
* x-goog-meta-file-hash
* x-goog-meta-child-of
