# Exclude PHP files from Composer

[![Build Status][travis-badge]][travis-ci.com]
[![Coverage Status][coveralls-badge]][coveralls.io]
[![License][license-badge]][packagist.org]
![GitHub Tag][release-badge]

A Composer plugin for excluding files required by packages using the `files`
autoloading mechanism.

This is useful for ignoring files required for bootstrapping a package or that
provide PHP functions, for example.

Resolves [composer/composer#5029](//github.com/composer/composer/issues/5029)

## Installation

The plugin can be installed locally or globally.

```shell
composer require mcaskill/composer-exclude-files
```

## Usage

> You can only ignore files from the root `composer.json`.
> File exclusions of dependencies' `composer.json` are ignored.

From the root `composer.json`, add the `exclude-from-files` property to the
`extra` section. The list of paths must be relative to this composer manifest's
vendor directory.

This plugin is invoked before the autoloader is dumped, such as with the
commands `install`, `update`, and `dump-autoload`.

###### Example 1: Using illuminate/support

```json
{
    "require": {
        "illuminate/support": "^9.6"
    },
    "extra": {
        "exclude-from-files": [
            "illuminate/support/helpers.php"
        ]
    }
}
```

###### Example 2: Using laravel/framework

```json
{
    "require": {
        "laravel/framework": "^9.6"
    },
    "extra": {
        "exclude-from-files": [
            "laravel/framework/src/Illuminate/Foundation/helpers.php"
        ]
    }
}
```

The plugin will traverse each package and remove all files in the paths
configured above from the prepared autoload map. The vendor files themselves
are not removed. The root package is ignored.

The resulting effect is the specified files are never included in
`vendor/composer/autoload_files.php`.

## License

This is licensed under MIT.

[travis-badge]:    https://app.travis-ci.com/mcaskill/composer-plugin-exclude-files.svg?branch=main
[coveralls-badge]: https://coveralls.io/repos/github/mcaskill/composer-plugin-exclude-files/badge.svg?branch=main
[license-badge]:   https://poser.pugx.org/mcaskill/composer-exclude-files/license
[release-badge]:   https://img.shields.io/github/tag/mcaskill/composer-plugin-exclude-files.svg

[travis-ci.com]:   https://app.travis-ci.com/github/mcaskill/composer-plugin-exclude-files
[coveralls.io]:    https://coveralls.io/github/mcaskill/composer-plugin-exclude-files?branch=main
[packagist.org]:   https://packagist.org/packages/mcaskill/composer-exclude-files
