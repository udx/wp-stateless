[![PHPUnit ](.github/coverage.svg)](https://brianhenryie.github.io/strauss/) [![PHPStan ](https://img.shields.io/badge/PHPStan-Level%207-2a5ea7.svg)](https://phpstan.org/)

# Strauss – PHP Namespace Renamer

A tool to prefix namespaces, classnames, and constants in PHP files to avoid autoloading collisions.

A fork of [Mozart](https://github.com/coenjacobs/mozart/) for [Composer](https://getcomposer.org/) for PHP.

Have you ever activated a WordPress plugin that has a conflict with another because the plugins use two different versions of the same PHP library? **Strauss is the solution to that problem** - it ensures that _your_ plugin's PHP dependencies are isolated and loaded from your plugin rather than loading from whichever plugin's autoloader registers & runs first.

> ⚠️ **Sponsorship**: I don't want your money. [Please write a unit test to help the project](https://brianhenryie.github.io/strauss/).

## Table of Contents

* [Installation](#installation)
    * [As a `.phar` file](#as-a-phar-file-within-composerjson-recommended) (recommended)
    * [As a dev dependency via composer](#as-a-dev-dependency-via-composer-not-recommended)  (not recommended)
    * [Adding `composer dump-autoload`](#adding-composer-dump-autoload)
* [Usage](#usage)
* [Configuration](#configuration)
* [Autoloading](#autoloading)
* [Motivation & Comparison to Mozart](#motivation--comparison-to-mozart)
* [Alternatives](#alternatives)
* [Breaking Changes](#breaking-changes)
* [Acknowledgements](#acknowledgements)

## Installation

### As a `.phar` file (recommended)

There are a couple of small steps to make this possible.

#### Create a `bin/.gitkeep` file

This ensures that there is a `bin/` directory in the root of your project. This is where the `.phar` file will go.

```bash
mkdir bin
touch bin/.gitkeep
```

#### `.gitignore` the `.phar` file

Add the following to your `.gitignore`:

```bash
bin/strauss.phar
```

#### Edit `composer.json` `scripts

In your `composer.json`, add `strauss` to the `scripts` section:

```json
"scripts": {
    "prefix-namespaces": [
        "sh -c 'test -f ./bin/strauss.phar || curl -o bin/strauss.phar -L -C - https://github.com/BrianHenryIE/strauss/releases/latest/download/strauss.phar'",
        "@php bin/strauss.phar",
        "@php composer dump-autoload"
    ],
    "post-install-cmd": [
        "@prefix-namespaces"
    ],
    "post-update-cmd": [
        "@prefix-namespaces"
    ]
}
```

This provides `composer strauss`, which does the following:

1. The `sh -c` command tests if `bin/strauss.phar` exists, and if not, downloads it from [releases](https://github.com/BrianHenryIE/strauss/releases).
2. Then `@php bin/strauss.phar` is run to prefix the namespaces.
3. Ensure that composer's autoload map is updated.

### As a dev dependency via composer (not recommended)

If you prefer to include Strauss as a dev dependency, you can still do so. You mileage may vary when you include it this way.

```
composer require --dev brianhenryie/strauss
```

#### Edit `composer.json` `scripts

```json
"scripts": {
    "prefix-namespaces": [
        "strauss",
        "@php composer dump-autoload"
    ],
    "post-install-cmd": [
        "@prefix-namespaces"
    ],
    "post-update-cmd": [
        "@prefix-namespaces"
    ]
}
```

## Usage

If you add Strauss to your `composer.json` as indicated in [Installation](#installation), it will run when you `composer install` or `composer update`. To run Strauss directly, simply use:

```bash
composer prefix-namespaces
```

To update the files that call the prefixed classes, you can use `--updateCallSites=true` which uses your autoload key, or `--updateCallSites=includes,templates` to explicitly specify the files and directories.

```bash
composer -- prefix-namespaces --updateCallSites=true
```

or

```bash
composer -- prefix-namespaces --updateCallSites=includes,templates
```

## Configuration

Strauss potentially requires zero configuration, but likely you'll want to customize a little, by adding in your `composer.json` an `extra/strauss` object. The following is the default config, where the `namespace_prefix` and `classmap_prefix` are determined from your `composer.json`'s `autoload` or `name` key and `packages` is determined from the `require` key:

```json
"extra": {
    "strauss": {
        "target_directory": "vendor-prefixed",
        "namespace_prefix": "BrianHenryIE\\My_Project\\",
        "classmap_prefix": "BrianHenryIE_My_Project_",
        "constant_prefix": "BHMP_",
        "packages": [
        ],
        "update_call_sites": false,
        "override_autoload": {
        },
        "exclude_from_copy": {
            "packages": [
            ],
            "namespaces": [
            ],
            "file_patterns": [
            ]
        },
        "exclude_from_prefix": {
            "packages": [
            ],
            "namespaces": [
            ],
            "file_patterns": [
            ]
        },
        "namespace_replacement_patterns" : {
        },
        "delete_vendor_packages": false,
        "delete_vendor_files": false
    }
},
```

The following configuration is inferred:

- `target_directory` defines the directory the files will be copied to, default `vendor-prefixed`
- `namespace_prefix` defines the default string to prefix each namespace with
- `classmap_prefix` defines the default string to prefix class names in the global namespace
- `packages` is the list of packages to process. If absent, all packages in the `require` key of your `composer.json` are included
- `classmap_output` is a `bool` to decide if Strauss will create `autoload-classmap.php` and `autoload.php`. If it is not set, it is `false` if `target_directory` is in your project's `autoload` key, `true` otherwise.

The following configuration is default:

- `delete_vendor_packages`: `false` a boolean flag to indicate if the packages' vendor directories should be deleted after being processed. It defaults to false, so any destructive change is opt-in.
- `delete_vendor_files`: `false` a boolean flag to indicate if files copied from the packages' vendor directories should be deleted after being processed. It defaults to false, so any destructive change is opt-in. This is maybe deprecated! Is there any use to this that is more appropriate than `delete_vendor_packages`?
- `include_modified_date` is a `bool` to decide if Strauss should include a date in the (phpdoc) header written to modified files. Defaults to `true`.
- `include_author` is a `bool` to decide if Strauss should include the author name in the (phpdoc) header written to modified files. Defaults to `true`.


- `update_call_sites`: `false`. This can be `true`, `false` or an `array` of directories/filepaths. When set to `true` it defaults to the directories and files in the project's `autoload` key. The PHP files and directories' PHP files will be updated where they call the prefixed classes.

The remainder is empty:

- `constant_prefix` is for `define( "A_CONSTANT", value );` -> `define( "MY_PREFIX_A_CONSTANT", value );`. If it is empty, constants are not prefixed (this may change to an inferred value).
- `override_autoload` a dictionary, keyed with the package names, of autoload settings to replace those in the original packages' `composer.json` `autoload` property.
- `exclude_from_prefix` / [`file_patterns`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/ChangeEnumerator.php#L92-L96)
- `exclude_from_copy`
  - [`packages`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/FileEnumerator.php#L77-L79) array of package names to be skipped
  - [`namespaces`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/FileEnumerator.php#L95-L97) array of namespaces to skip (exact match from the package autoload keys)
  - [`file_patterns`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/FileEnumerator.php#L133-L137) array of regex patterns to check filenames against (including vendor relative path) where Strauss will skip that file if there is a match
- `exclude_from_prefix`
  - [`packages`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/ChangeEnumerator.php#L86-L90) array of package names to exclude from prefixing.
  - [`namespaces`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/ChangeEnumerator.php#L177-L181) array of exact match namespaces to exclude (i.e. not substring/parent namespaces)
- [`namespace_replacement_patterns`](https://github.com/BrianHenryIE/strauss/blob/83484b79cfaa399bba55af0bf4569c24d6eb169d/src/ChangeEnumerator.php#L183-L190) a dictionary to use in `preg_replace` instead of prefixing with `namespace_prefix`.

## Autoloading

Strauss uses Composer's own tools to generate a classmap file in the `target_directory` and creates an `autoload.php` alongside it, so in many projects autoloading is just a matter of:

```php
require_once __DIR__ . '/strauss/autoload.php';
```

If you prefer to use Composer's autoloader, add your `target_directory` (default `vendor-prefixed`) to your `autoload` `classmap` and Strauss will not create its own `autoload.php` when run. Then run `composer dump-autoload` to include the newly copied and prefixed files in Composer's own classmap.

```
"autoload": {
    "classmap": [
        "vendor-prefixed/"
    ]
},
```

## Motivation & Comparison to Mozart

I was happy to make PRs to Mozart to fix bugs, but they weren't being reviewed and merged. At the time of writing, somewhere approaching 50% of Mozart's code [was written by me](https://github.com/coenjacobs/mozart/graphs/contributors) with an additional [nine open PRs](https://github.com/coenjacobs/mozart/pulls?q=is%3Apr+author%3ABrianHenryIE+) and the majority of issues' solutions [provided by me](https://github.com/coenjacobs/mozart/issues?q=is%3Aissue+). This fork is a means to merge all outstanding bugfixes I've written and make some more drastic changes I see as a better approach to the problem.

Benefits over Mozart:

* A single output directory whose structure matches source vendor directory structure (conceptually easier than Mozart's independent `classmap_directory` and `dep_directory`)
* A generated `autoload.php` to `include` in your project (analogous to Composer's `vendor/autoload.php`)
* Handles `files` autoloaders – and any autoloaders that Composer itself recognises, since Strauss uses Composer's own tooling to parse the packages
* Zero configuration – Strauss infers sensible defaults from your `composer.json`
* No destructive defaults – `delete_vendor_files` defaults to `false`, so any destruction is explicitly opt-in
* Licence files are included and PHP file headers are edited to adhere to licence requirements around modifications. My understanding is that re-distributing code that Mozart has handled is non-compliant with most open source licences – illegal!
* Extensively tested – PhpUnit tests have been written to validate that many of Mozart's bugs are not present in Strauss
* More configuration options – allowing exclusions in copying and editing files, and allowing specific/multiple namespace renaming
* Respects `composer.json` `vendor-dir` configuration
* Prefixes constants (`define`)
* Handles meta-packages and virtual-packages

Strauss will read the Mozart configuration from your `composer.json` to enable a seamless migration.

## Alternatives

I don't have a strong opinion on these. I began using Mozart because it was easy, then I adapted it to what I felt was most natural. I've never used these.

* [humbug/php-scoper](https://github.com/humbug/php-scoper)
* [TypistTech/imposter-plugin](https://github.com/TypistTech/imposter-plugin)
* [Automattic/jetpack-autoloader](https://github.com/Automattic/jetpack-autoloader)
* [tschallacka/wordpress-composer-plugin-builder](https://github.com/tschallacka/wordpress-composer-plugin-builder)
* [Interfacelab/namespacer](https://github.com/Interfacelab/namespacer)
* [PHP-Prefixer](https://github.com/PHP-Prefixer) SaaS!

### Interesting

* [composer-unused/composer-unused](https://github.com/composer-unused/composer-unused)
* [sdrobov/autopsr4](https://github.com/sdrobov/autopsr4)
* [jaem3l/unfuck](https://github.com/jaem3l/unfuck)

## Breaking Changes

* v0.16.0 – will no longer prefix PHP built-in classes seen in polyfill packages
* v0.14.0 – `psr/*` packages no longer excluded by default
* v0.12.0 – default output `target_directory` changes from `strauss` to `vendor-prefixed`

Please open issues to suggest possible breaking changes. I think we can probably move to 1.0.0 soon.

## Changes before v1.0

* Comprehensive attribution of code forked from Mozart – changes have been drastic and `git blame` is now useless, so I intend to add more attributions
* More consistent naming. Are we prefixing or are we renaming?
* Further unit tests, particularly file-system related
* Regex patterns in config need to be validated
* Change the name? "Renamespacer"?

## Changes before v2.0

The correct approach to this problem is probably via [PHP-Parser](https://github.com/nikic/PHP-Parser/). At least all the tests will be useful.

## Acknowledgements

[Coen Jacobs](https://github.com/coenjacobs/) and all the [contributors to Mozart](https://github.com/coenjacobs/mozart/graphs/contributors), particularly those who wrote nice issues.
