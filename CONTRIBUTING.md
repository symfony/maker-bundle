# Contributing

This project is part of the Symfony ecosystem and follows the
[Symfony Contribution Guidelines](https://symfony.com/doc/current/contributing/index.html).

## Non-interactive makers

Every maker command must also work with `--no-interaction`. See [AGENTS.md](AGENTS.md) for
the patterns this requires and the mistakes that break it.

## Running Tests

This project uses [PHPUnit Bridge](https://phpunit.de/getting-started/phpunit-bridge.html) to run the tests.

Install the required dependencies with:

```bash
composer install
```

To run the tests, use the following command:

```bash
./vendor/bin/simple-phpunit
```

## Static Analysis

This project uses [PHPStan](https://phpstan.org/) for static analysis.
To run PHPStan, use the following command:

Install the required dependencies for the project, PHPStan itself and the extra
packages that the project uses:

```bash
composer update
composer update --working-dir=tools/phpstan
composer update --working-dir=tools/phpstan/includes
```
Run PHPStan with:

```bash
tools/phpstan/vendor/bin/phpstan
```

## Style Checking

This project uses [PHP CS Fixer](https://cs.symfony.com/) to ensure code style consistency.

Install the required dependencies with:

```bash
composer update --working-dir=tools/php-cs-fixer
```

To fix the code style, run:

```bash
tools/php-cs-fixer/vendor/bin/php-cs-fixer fix
```

## Bundled PHP CS Fixer

The PHP-CS-Fixer package is bundled with this project and used by some makers.

To update the Phar file to the latest version, run:

```bash
curl -fsSLo src/Resources/bin/php-cs-fixer.phar https://cs.symfony.com/download/php-cs-fixer-v3.phar
chmod a+x src/Resources/bin/php-cs-fixer.phar
```

Get the version of the downloaded Phar file:

```bash
php src/Resources/bin/php-cs-fixer.phar --version
```

Update the `BUNDLED_PHP_CS_FIXER_VERSION` constant in `src/Util/TemplateLinter.php`:

```diff
-     public const BUNDLED_PHP_CS_FIXER_VERSION = '3.49.0';
+     public const BUNDLED_PHP_CS_FIXER_VERSION = '3.92.5';
```
