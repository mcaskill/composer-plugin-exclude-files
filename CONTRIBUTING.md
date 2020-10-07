# How to Contribute

Please note that this project is released with a [Contributor Code of Conduct][CODE_OF_CONDUCT].
By participating in this project you agree to abide by its terms.

## Reporting Issues

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

## Coding Style Fixes

We do not accept CS fixes pull requests. Fixes are done by the project
maintainers when appropriate to avoid causing too many unnecessary conflicts
between branches and pull requests.

## Security Reports

Please send any sensitive issue to [chauncey@mcaskill.ca](mailto:chauncey@mcaskill.ca). Thanks!

## Contributing Policy

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-2 Coding Standards][PSR-2]. You can also run [PHP-CS-Fixer] with
the configuration file that can be found in the project root directory.

All pull requests must be accompanied by passing unit tests and complete code coverage.
The Composer plugin uses [PHPUnit] for testing.

## Attribution

These guidelines are adapted from [Composer's contribution guidelines][CONTRIBUTING].

[CODE_OF_CONDUCT]: https://github.com/mcaskill/composer-plugin-exclude-files/blob/master/CODE_OF_CONDUCT.md
[CONTRIBUTING]:    https://github.com/composer/composer/blob/1.10.13/.github/CONTRIBUTING.md
[PSR-2]:           https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PHP-CS-Fixer]:    https://github.com/FriendsOfPHP/PHP-CS-Fixer
[PHPUnit]:         https://github.com/sebastianbergmann/phpunit
