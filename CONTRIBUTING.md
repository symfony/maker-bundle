# Contributing

This project is part of the Symfony ecosystem and follows the
[Symfony Contribution Guidelines](https://symfony.com/doc/current/contributing/index.html).

## Running Tests

Install the required dependencies with:

```bash
composer install
```

The tests are split into two suites, `unit` and `makers`:

```bash
composer test:unit             # fast unit tests
composer test:makers           # functional suite: runs every maker against real generated apps
composer test:makers:parallel  # the makers suite on 4 parallel workers
```

To run a single maker's tests on the warm cache (takes seconds):

```bash
composer test:makers -- --filter=MakeCommand
```

## The Functional Test Suite

The functional tests (`tests/Functional/`, engine in `tests/Harness/`) execute
each maker inside a real Symfony application and then **actually run the
generated code**: generated tests are executed with the app's own PHPUnit,
entities hit a real database, templates are really rendered.

How it works, in short:

* **App profiles.** `tests/Harness/Application/ProfileCatalog.php` is the single
  place that declares the applications tests run against (`base`, `twig`,
  `doctrine`, ...). Each profile is a real `symfony/skeleton` app with a fixed
  set of packages, built once and cached under `tests/tmp/`. Every direct
  requirement carries a reason (`declared-by-maker`, `harness-only`, ...) so a
  maker can never silently rely on an undeclared package. Test classes declare
  their profile with `#[MakerTest(maker: ..., profile: ...)]`.
* **Warm apps.** Each PHPUnit worker gets its own copy of the app, warmed once;
  between tests it is reset in ~0.3s with the compiled container kept warm.
  Your local edits to `src/`, `config/` and `templates/` of the bundle apply
  live (the apps see the checkout through a symlinked path repository); the
  harness detects what changed and rebuilds only what that change requires.
* **Prompt-matched input.** Interactive answers are declared against the prompt
  text: `->answer('Choose a command name', 'app:foo')`. If a maker gains,
  loses or reorders a prompt, the test fails with the actual prompt and a full
  transcript — never by silently accepting defaults.
* **The execution contract.** Everything a maker generates must be exercised
  for real, per file: executors declare what they cover
  (`covers: ['src/Command/FooCommand.php']`) and the harness cross-checks that
  against what the runs actually loaded. A test that generates a file without
  executing it fails in teardown. Documented exception: `compose.yaml` files
  are only validated with `docker compose config`.
* **Provenance.** Test methods carry `#[LegacyCase('OldClass::old_yield_key')]`
  attributes documenting which case of the pre-2026 suite they descend from.
  The `src/Test/` classes that suite was built on are frozen: they are kept
  for third-party bundles, MakerBundle's own tests no longer use them.

Useful environment switches:

| Variable | Effect |
|---|---|
| `SYMFONY_VERSION` | skeleton version for the generated apps (e.g. `6.4.*`, `^8`) |
| `TEST_DATABASE_DSN` | run doctrine cases against a real server instead of sqlite |
| `MAKER_KEEP_APP=1` | copy the app of a failing test to `tests/tmp/kept/...` for inspection |
| `MAKER_SKIP_TWIGCS=1` | skip Twig style linting locally |
| `MAKER_SKIP_JS_EXEC=1` | skip the Stimulus-executor cases (no Node available) |
| `MAKER_SKIP_MERCURE_TEST=1` / `MAKER_SKIP_PANTHER_TEST=1` | skip cases needing a Mercure hub / a browser |
| `MAKER_PROCESS_TIMEOUT=null` | disable process timeouts (step debugging) |

Maintenance commands:

```bash
composer test:apps:build      # prebuild all app profiles (otherwise built lazily)
composer test:apps:validate   # verify profiles boot warm, without recompiling
composer test:apps:clean      # remove all built apps and caches
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
