# Changelog

## [Unreleased]

* Added support for glob (wildcard) paths to exclude files via new value object classes for paths.
* Updated tests to support meta-packages and immutable packages.
* Refactored tests to decouple set-up and tear-down, sort methods by visibility and alphabetically, and improve static analysis.

## [3.0.1] — 2023-05-24

* Fixed support for changes to metapackages
  in [Composer 2.5.6](https://github.com/composer/composer/releases/tag/2.5.6)
  and [Composer 2.5.7](https://github.com/composer/composer/releases/tag/2.5.7)
* Updated tests

## [3.0.0] — 2022-09-12

* BC Break: Minimum supported Composer plugin API version is now 2.3.0
  (see [release for Composer 2.3.0](https://github.com/composer/composer/releases/tag/2.3.0))
* BC Break: Minimum supported PHP version is now 7.2.5
* BC Break: Added native parameter & return types
* Improved logic of plugin
* Add PHPStan for static analysis
* Cleaned-up tests

## [2.1.0] — 2022-04-01

* Fixed tests against Composer 1.0–2.3
* Fixed tests against PHP 5.3.2–8.1
* Fixed configuration of `.travis.yml`

## [2.0.0] — 2020-09-25

* BC Break: Removed support for `autoload.exclude-from-files`
* Added support for PHP 8.0+
* Added support for tests against PHP 8.0
* Fixed tests against Composer 2.0
* Fixed tests against PHP 5.3.2–7.0

## [1.3.0] — 2020-09-25

* Deprecated support for `autoload.exclude-from-files`

## [1.2.0] — 2020-05-10

* Added support for Composer v2 (#7)
* Added support for PHP 5.3.2–7.0+ (#6)
* Added support for tests against Composer 2.0
* Added support for tests against PHP 5.3.2–7.0
* Moved php-coveralls from `composer.json` to `.travis.yml`
* Replaced phpunit/phpunit with symfony/phpunit-bridge
* Replaced squizlabs/php_codesniffer with friendsofphp/php-cs-fixer

## [1.1.0] — 2018-12-18

* Fixed Windows path resolution (#2, #3, #4, #5)

## [1.0.0] — 2018-12-18

* Initial release.

[Unreleased]: https://github.com/mcaskill/composer-plugin-exclude-files/compare/v3.0.1...HEAD
[3.0.1]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v3.0.0...v3.0.1
[3.0.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v2.1.0...v3.0.0
[2.1.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v2.0.0...v2.1.0
[2.0.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.3.0...v2.0.0
[1.3.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.2.0...v1.3.0
[1.2.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.1.0...v1.2.0
[1.1.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.0.0...v1.1.0
[1.0.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/releases/tag/v1.0.0
