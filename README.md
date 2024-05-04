# Exclude PHP files from Composer

[![Build Status][github-badge]][github-actions]
[![Latest Release][release-badge]][github-latest]
[![License][license-badge]][packagist.org]

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

As of Composer 2.2.0, for [additional security][composer-allow-plugins], you
should declare the `allow-plugins` config to allow Composer to run the plugin.

```shell
composer config allow-plugins.mcaskill/composer-exclude-files true
```

## Usage

> You can only ignore files from the root `composer.json`.
> File exclusions of dependencies' `composer.json` are ignored.

From the root `composer.json`, add the `exclude-from-files` property to the
`extra` section. The list of paths must be relative to this Composer manifest's
vendor directory: `<vendor-name>/<project-name>/<file-path>`.

This plugin supports a subset of special characters used by
the [`glob()` function][php-function-glob] to match exclude paths
matching a pattern:

* `*` — Matches zero or more characters.
* `?` — Matches exactly one character (any character).

This plugin is invoked before the autoloader is dumped, such as with the
commands `install`, `update`, and `dump-autoload`.

###### Example 1: Excluding one file from illuminate/support

```json
{
    "require": {
        "illuminate/support": "^9.6"
    },
    "extra": {
        "exclude-from-files": [
            "illuminate/support/helpers.php"
        ]
    },
    "config": {
        "allow-plugins": {
            "mcaskill/composer-exclude-files": true
        }
    }
}
```

###### Example 2: Excluding many files from laravel/framework

```json
{
    "require": {
        "laravel/framework": "^9.6"
    },
    "extra": {
        "exclude-from-files": [
            "laravel/framework/src/*/helpers.php"
        ]
    },
    "config": {…}
}
```

###### Example 3: Excluding all files

```json
{
    "require": {…},
    "extra": {
        "exclude-from-files": [
            "*"
        ]
    },
    "config": {…}
}
```

The plugin will traverse each package and remove all files in the paths
configured above from the prepared autoload map. The vendor files themselves
are not removed. The root package is ignored.

The resulting effect is the specified files are never included in
`vendor/composer/autoload_files.php`.

## License

This is licensed under MIT.

[composer-allow-plugins]: https://getcomposer.org/allow-plugins
[php-function-glob]:      https://php.net/function.glob

[github-badge]:    https://img.shields.io/github/actions/workflow/status/mcaskill/composer-plugin-exclude-files/test.yml?branch=main
[license-badge]:   https://poser.pugx.org/mcaskill/composer-exclude-files/license
[release-badge]:   https://img.shields.io/github/tag/mcaskill/composer-plugin-exclude-files.svg

[github-actions]:  https://github.com/mcaskill/composer-plugin-exclude-files/actions
[github-latest]:   https://github.com/mcaskill/composer-plugin-exclude-files/releases/latest
[packagist.org]:   https://packagist.org/packages/mcaskill/composer-exclude-files
