# Changelog

## 2.0.0 — 2020-09-25

### Added

- Add support for PHP 8.0+
- Test against PHP 8.0

### Fixed

- Test against Composer 2.0
- Test against PHP 5.3.2–7.0

### Removed

- Remove support for `autoload.exclude-from-files`

## 1.3.0 — 2020-09-25

### Deprecated

- Support for `autoload.exclude-from-files`

## 1.2.0 — 2020-05-10

### Added

- Add support for Composer v2 [#7](https://github.com/mcaskill/composer-plugin-exclude-files/issues/7)
- Add support for PHP 5.3.2–7.0+ [#6](https://github.com/mcaskill/composer-plugin-exclude-files/issues/6)
- Test against Composer 2.0
- Test against PHP 5.3.2–7.0

### Changed

- Move php-coveralls from `composer.json` to `.travis.yml`
- Replace phpunit/phpunit with symfony/phpunit-bridge
- Replace squizlabs/php_codesniffer with friendsofphp/php-cs-fixer

## 1.1.0 — 2018-12-18

### Fixed

- Windows path resolution [#2](https://github.com/mcaskill/composer-plugin-exclude-files/issues/2) [#3](https://github.com/mcaskill/composer-plugin-exclude-files/issues/3) [#4](https://github.com/mcaskill/composer-plugin-exclude-files/issues/4) [#5](https://github.com/mcaskill/composer-plugin-exclude-files/issues/5)

## 1.0.0 — 2018-12-18

- Initial release.
