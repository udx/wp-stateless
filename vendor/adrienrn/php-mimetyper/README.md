# php-mimetyper

PHP mime type and extension mapping library: built with [jshttp/mime-db](http://github.com/jshttp/mime-db), compatible with Symfony and Laravel.

```php
use MimeTyper\Repository\MimeDbRepository;

$mimeRepository = new MimeDbRepository();

$mimeRepository->findExtensions("image/jpeg"); // ["jpeg","jpg","jpe"]
$mimeRepository->findExtension("image/jpeg"); // "jpeg"

$mimeRepository->findType("html"); // "html"
$mimeRepository->findType("js"); // 'application/javascript'

```

> The most complete and up-to-date mime type mapping for PHP!

The goal is to provide a complete and up-to-date mime types mapping for PHP and build a comprehensive and simple interface for PHP. This package is heavily inspired from [dflydev](https://github.com/dflydev/dflydev-apache-mime-types) work and extends it.

## Mime types mapping, the right way.

This library uses [jshttp/mime-db](http://github.com/jshttp/mime-db) as its default mapping which aggregates data from multiple sources and creates a single `db.json` making it the most complete two ways mapping, from mime to extension and extension to mime types too.

- [IANA](http://www.iana.org/assignments/media-types/media-types.xhtml)
- [Apache](http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types)
- [Nginx](http://hg.nginx.org/nginx/file/tip/conf/mime.types)
- Some (very) useful custom aliases;

## Custom mime types and custom repositories

Some custom types (aliases really) are maintained locally too, in the same JSON format as jshttp/mime-db. 

```php

use MimeTyper\Repository\ExtendedRepository;

$mimeRepostory = new ExtendedRepository();

$mimeRepository->findExtensions("text/x-php"); // ["php", "php2", "php3", "php4", "php5"]

$mimeRepository->findTypes("php"); // ["text/x-php", "application/x-php", "text/php", "application/php", "application/x-httpd-php"]
$mimeRepository->findType("php"); // "text/x-php"

```

The reason to maintain aliases locally helps with overall compatibility between mime type guessing methods. Tools detecting mime types don't always return standard mime type or the standard mime type does not exist. All of those custom mime types might be [added to jshttp/mime-db custom types in the end](https://github.com/jshttp/mime-db/issues/49).

**Example:** Debian will detect a PHP file as `text/x-php` while browsers will send `application/x-httpd-php`. It goes the same with files such as Javascript (`application/javascript` vs `text/javascript`) or Microsoft Office / Libre Office files.

Don't hesitate to make a pull request to discuss this.

## Mime types for Symfony and Laravel

This library is compatible with your Symfony or Laravel app to enjoy the completeness of the mapping.

Use the `ExtraMimeTypeExtensionGuesser` as a bridge class between Symfony `ExtensionGuesser` and this package `RepositoryInterface`.

```php

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

use MimeTyper\Repository\ExtendedRepository;
use MimeTyper\Symfony\ExtraMimeTypeExtensionGuesser;

$symfonyGuesser = ExtensionGuesser::getInstance();
$extraGuesser = new ExtraMimeTypeExtensionGuesser(
    new ExtendedRepository()
);
$symfonyGuesser->register($extraGuesser);

```

This example uses the `ExtendedRepository` (mime-db and local custom mime types), you can use the default `MimeDbRepository`, implement your own or use a `CompositeRepository` to aggregate multiple repostories.

## Safe detection of mime type in PHP

Before mapping type to extension or extension to type, you need to be able to properly detect the mime type of a file.

For security reasons, **do not trust browsers**, eg `$_FILES['your_file']['type']`, when it comes to detect the mime type of a file.

To safely detect the mime type of a file, . Symfony is giving a great example with their MimeTypeGuesser implementation of:

- [FileinfoMimeTypeGuesser](https://github.com/symfony/http-foundation/blob/3.1/File/MimeType/FileinfoMimeTypeGuesser.php)
- [FileBinaryMimeTypeGuesser](https://github.com/symfony/http-foundation/blob/3.1/File/MimeType/FileBinaryMimeTypeGuesser.php)

It all ends up inspecting the file using [finfo](http://php.net/manual/en/function.finfo-open.php) and relies on magic db files. PHP will use its own magic db or your system magic db depending on your environement.

## Other PHP libraries for mime types

- [dflydev/dflydev-apache-mime-types](https://github.com/dflydev/dflydev-apache-mime-types)

  Uses `mime.types` Apache file, comprehensive api. As stated before, php-mimetyper is heavily inspired by this, extending it to be a bit more complete using an external mapping and a wider interface.

- [symfony/http-foundation](https://github.com/symfony/http-foundation/tree/master/File/MimeType)

  Symfony provides a nice interface for guessing mime types and extensions but uses only a local mapping based on Apache registry, see above to bridge it to this package.

- [davidpersson/mm](https://github.com/davidpersson/mm)

  Library for media processing and mime type and extension guessing. Uses FreeDesktop magic.db file for the latter.

- [Hoa/Mime](https://github.com/hoaproject/Mime)

  The Hoa package to deal with mime types. Uses `mime.types` Apache file (local fallback) and relies on static methods.

- [karwana/php-mime](https://github.com/karwana/php-mime)
  
  Uses `mime.types` Apache file and finfo, requires PHP >=5.4.

- [PEAR/MIME_Type](https://github.com/pear/MIME_Type)

  Detect the mime type of a file: uses internally finfo_file, mime_content_type or file command to guess the mime type.

