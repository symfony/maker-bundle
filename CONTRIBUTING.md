# Contributing

This project is part of the Symfony ecosystem and follows the
[Symfony Contribution Guidelines](https://symfony.com/doc/current/contributing/index.html).

## Non-interactive makers

Every maker command must also work with `--no-interaction`. See [AGENTS.md](AGENTS.md) for
the patterns this requires and the mistakes that break it.

## Project policy

Prefer non-breaking changes, and document any break in the PR.

### Avoid breaking template compatibility

Templates are a customization point: users override them to adapt the generated code.
Once a template can be overridden, its file name and the variables passed to it become a
public contract. Keep them stable by default, but a break is not forbidden:

- Prefer adding a new variable over changing an existing one.
- Prefer adding a new template file over renaming or moving an existing one.
- A rename or a removal is acceptable when it is worth it. Mention it in the PR, and
  keep the old name working when that is cheap.

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
