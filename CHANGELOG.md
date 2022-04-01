# Changelog

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

[Unreleased]: https://github.com/mcaskill/composer-plugin-exclude-files/compare/v2.1.0...HEAD
[2.1.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v2.0.0...v2.1.0
[2.0.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.3.0...v2.0.0
[1.3.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.2.0...v1.3.0
[1.2.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.1.0...v1.2.0
[1.1.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/compare/v1.0.0...v1.1.0
[1.0.0]:      https://github.com/mcaskill/composer-plugin-exclude-files/releases/tag/v1.0.0
