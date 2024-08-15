# How to contribute

Please note that this project is released with a
[Contributor Code of Conduct][CODE_OF_CONDUCT].
By participating in this project you agree to abide by its terms.

## Reporting issues

When reporting issues, please try to be as descriptive as possible, and include
as much relevant information as you can. A step by step guide on how to
reproduce the issue will greatly increase the chances of your issue being
resolved in a timely manner.

For example, if you are experiencing a problem while running one of the
commands, please provide full output of said command in very very verbose mode
(`-vvv`, e.g. `composer install -vvv`).

If your issue involves installing, updating or resolving dependencies, the
chance of us being able to reproduce your issue will be much higher if you
share your `composer.json` with us.

## Coding style fixes

We do not accept CS fixes pull requests. Fixes are done by the project
maintainers when appropriate to avoid causing too many unnecessary conflicts
between branches and pull requests.

## Security reports

Please send any sensitive issue to [chauncey@mcaskill.ca](mailto:chauncey@mcaskill.ca).
Thanks!

## Installation from source

Prior to contributing to Composer, you must be able to run the test suite.
To achieve this, you need to acquire the Composer source code:

1. Run `git clone https://github.com/mcaskill/composer-plugin-exclude-files.git`
2. Download the [`composer.phar`](https://getcomposer.org/composer.phar) executable
3. Run Composer to get the dependencies: `cd composer-plugin-exclude-files && php ../composer.phar install`

You can run the test suite by executing `vendor/bin/simple-phpunit` when inside the
composer directory, and run Composer by executing the `bin/composer`.

For running the tests against the most recent PHP versions (PHP 8.0/8.1), you will
need to run `composer update --ignore-platform-reqs && git checkout composer.lock`  before running 
the `vendor/bin/simple-phpunit` command.

To test your modified Composer code against another project, run
`php /path/to/composer/bin/composer` inside that project's directory.

## Contributing policy

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PER Coding Style 2.0][PER-Coding-Style]. You can also run [PHP-CS-Fixer]
with the configuration file that can be found in the project root directory.

If you would like to help, take a look at the [list of open issues][issues].

## Attribution

These guidelines are adapted from
[Composer's contribution guidelines][Composer-CONTRIBUTING].

[CODE_OF_CONDUCT]:       https://github.com/mcaskill/composer-plugin-exclude-files/blob/main/CODE_OF_CONDUCT.md
[Composer-CONTRIBUTING]: https://github.com/composer/composer/blob/2.3.2/.github/CONTRIBUTING.md
[issues]:                https://github.com/mcaskill/composer-plugin-exclude-files/issues
[PER-Coding-Style]:      https://www.php-fig.org/per/coding-style/
[PHP-CS-Fixer]:          https://github.com/FriendsOfPHP/PHP-CS-Fixer
[PHPUnit]:               https://github.com/sebastianbergmann/phpunit
