# Exclude PHP files from Composer

[![Build Status](https://travis-ci.org/mcaskill/composer-plugin-exclude-files.svg?branch=master)](https://travis-ci.org/mcaskill/composer-plugin-exclude-files)
[![Coverage Status](https://coveralls.io/repos/github/mcaskill/composer-plugin-exclude-files/badge.svg?branch=master)](https://coveralls.io/github/mcaskill/composer-plugin-exclude-files?branch=master)
[![License](https://poser.pugx.org/mcaskill/composer-exclude-files/license)](https://packagist.org/packages/mcaskill/composer-exclude-files)
![GitHub tag](https://img.shields.io/github/tag/mcaskill/composer-plugin-exclude-files.svg)

A Composer plugin for excluding files required by packages using the 'files' autoloading mechanism.

This is useful for ignoring files required for bootstrapping a package or that provide PHP functions, for example.

Resolves [composer/composer#5029](//github.com/composer/composer/issues/5029)

## Installation

The plugin can be installed locally or globally.

```
$ composer require mcaskill/composer-exclude-files
```

## Usage

> You can only ignore files from your main `composer.json`.  
> File exclusions of dependencies' `composer.json`s are ignored.

From your main `composer.json`, add the `exclude-from-files` property to either the 'autoload' section or the 'extra' section.
The list of paths must be absolute from the vendor directory.

This plugin is invoked before the autoloader is dumped, either during `install`/`update`, or via the `dump-autoload` command.

Example:

```
{
    "require": {
        "illuminate/support": "^5.5"
    },
    "autoload": {
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
