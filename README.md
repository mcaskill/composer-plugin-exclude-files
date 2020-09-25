# Exclude PHP files from Composer

[![Build Status][travis-badge]][travis-ci.org]
[![Coverage Status][coveralls-badge]][coveralls.io]
[![License][license-badge]][packagist.org]
![GitHub Tag][release-badge]

A Composer plugin for excluding files required by packages using the 'files' autoloading mechanism.

This is useful for ignoring files required for bootstrapping a package or that provide PHP functions, for example.

Resolves [composer/composer#5029](//github.com/composer/composer/issues/5029)

## Installation

The plugin can be installed locally or globally.

```console
$ composer require mcaskill/composer-exclude-files
```

## Usage

> You can only ignore files from your main `composer.json`.
> File exclusions of dependencies' `composer.json`s are ignored.

From your main `composer.json`, add the `exclude-from-files` property to either the `extra` section.
The list of paths must be relative to the composer manifest.

This plugin is invoked before the autoloader is dumped, either during `install`/`update`, or via the `dump-autoload` command.

Example:

```json
{
    "require": {
        "illuminate/support": "^5.5"
    },
    "extra": {
        "exclude-from-files": [
            "illuminate/support/helpers.php"
        ]
    }
}
```

The plugin will traverse each package and remove all files in the paths configured above from the prepared autoload map. The vendor files themselves are not removed. The root package is ignored.

The resulting effect is the specified files are never included in `vendor/composer/autoload_files.php`.

## License

This is licensed under MIT.

[travis-badge]:    https://travis-ci.org/mcaskill/composer-plugin-exclude-files.svg?branch=master
[coveralls-badge]: https://coveralls.io/repos/github/mcaskill/composer-plugin-exclude-files/badge.svg?branch=master
[license-badge]:   https://poser.pugx.org/mcaskill/composer-exclude-files/license
[release-badge]:   https://img.shields.io/github/tag/mcaskill/composer-plugin-exclude-files.svg

[travis-ci.org]:   https://travis-ci.org/mcaskill/composer-plugin-exclude-files
[coveralls.io]:    https://coveralls.io/github/mcaskill/composer-plugin-exclude-files?branch=master
[packagist.org]:   https://packagist.org/packages/mcaskill/composer-exclude-files
