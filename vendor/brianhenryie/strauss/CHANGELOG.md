# Change Log

## 0.19.5 October 2024

* Fix: `use GlobalClass as Alias;` not prefixed
* Add: `.gitattributes` file to exclude dev files from distribution
* CI: Fail releases if `bin/strauss` version number is out of sync
* Tests: Add first tests for `DiscoveredFiles.php`
* Improve `README.md`
* Fix: typos in code

## 0.19.4 October 2024

* Fix: out of sync version number in `bin/strauss`

## 0.19.3 October 2024

* Fix: handle `@` symbol for error suppression
* Fix: handle `preg_replace...` returning `null` in `Licenser`
* Fix: only search for symbols in PHP files

## 0.19.2 June 2024

* Fix: available CLI arguments were overwriting extra.strauss config
* Fix: updating `league/flysystem` changed the default directory permissions

## 0.19.1 April 2024

* Fix: was incorrectly deleting autoload keys from installed.json

## 0.19.0 April 2024

* Fix: check for array before loop
* Fix: filepaths on Windows (still work to do for Windows)
* Update: tidy `bin/strauss`
* Run tests with project classes + with built phar
* Allow `symfony/console` & `symfony/finder` `^7` for Laravel 11 compatibility
* Add: `scripts/createphar.sh`
* Lint: most PhpStan level 7

## 0.18.0 April 2024

* Add: GitHub Action to update bin version number from CHANGELOG.md
* Fix: casting a namespaced class to a string
* Fix: composer dump-autoload error after delete-vendor-files/delete-vendor-packages
* Fix: add missing built-in PHP interfaces to exclude rules
* Fix: Undefined offset when seeing namespace
* Refactoring for clarity and pending issues

## 0.14.0 07-March-2023

* Merge `in-situ` branch (bugs expected)
* Add: `delete_vendor_packages` option (`delete_vendor_files` is maybe deprecated now)
* Add: GPG .phar signing for Phive
* Breaking: Stop excluding `psr/*` from `file_patterns` prefixing
