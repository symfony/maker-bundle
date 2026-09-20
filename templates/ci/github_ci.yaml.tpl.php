name: CI

env:
    APP_ENV: test
    PHP_VERSION: '<?= $php_version; ?>'
<?php if ($database_url): ?>
    DATABASE_URL: '<?= $database_url; ?>'
<?php endif; ?>

on:
    push:
        branches:
            - '<?= str_replace("'", "''", $default_branch); ?>'
    pull_request:

permissions:
    contents: read

concurrency:
    group: ${{ github.workflow }}-${{ github.ref }}
    cancel-in-progress: true

jobs:
<?php if ($has_phpunit): ?>
    tests:
        name: Tests
        runs-on: ubuntu-latest
<?php if ('postgres' === $database_type): ?>

        services:
            postgres:
                image: postgres:16-alpine
                env:
                    POSTGRES_DB: app
                    POSTGRES_USER: app
                    POSTGRES_PASSWORD: password
                ports:
                    - 5432:5432
                options: >-
                    --health-cmd "pg_isready -d app -U app"
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5
<?php elseif ('mysql' === $database_type): ?>

        services:
            mysql:
                image: mysql:8.0
                env:
                    MYSQL_ROOT_PASSWORD: password
                    MYSQL_DATABASE: app
                ports:
                    - 3306:3306
                options: >-
                    --health-cmd "mysqladmin ping -h 127.0.0.1"
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5
<?php elseif ('mariadb' === $database_type): ?>

        services:
            mariadb:
                image: mariadb:10.11
                env:
                    MYSQL_ROOT_PASSWORD: password
                    MYSQL_DATABASE: app
                ports:
                    - 3306:3306
                options: >-
                    --health-cmd "mysqladmin ping -h 127.0.0.1"
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5
<?php endif; ?>

        steps:
            - name: Checkout
              uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1

            - name: Setup PHP
              uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # 2.37.2
              with:
                  php-version: ${{ env.PHP_VERSION }}
                  coverage: none

            - name: Install dependencies
              uses: ramsey/composer-install@65e4f84970763564f46a70b8a54b90d033b3bdda # 4.0.0
              with:
                  require-lock-file: true
<?php if ($has_doctrine && $has_doctrine_migrations && $database_type): ?>

            - name: Setup database
              run: |
<?php if ('sqlite' !== $database_type): ?>
                  php bin/console doctrine:database:create --if-not-exists
<?php endif; ?>
                  php bin/console doctrine:migrations:migrate --no-interaction

            - name: Validate database schema
              run: |
                  php bin/console doctrine:schema:validate || {
                      echo "::group::Schema changes"
                      php bin/console doctrine:schema:update --dump-sql
                      echo "::endgroup::"
                      exit 1
                  }
<?php elseif ($has_doctrine && $database_type): ?>

            - name: Validate database schema
              run: php bin/console doctrine:schema:validate --skip-sync -vvv --no-interaction

            - name: Setup database
              run: |
<?php if ('sqlite' !== $database_type): ?>
                  php bin/console doctrine:database:create --if-not-exists
<?php endif; ?>
                  php bin/console doctrine:schema:update --force
<?php endif; ?>

            - name: Run tests
              run: vendor/bin/phpunit

<?php endif; ?>
    lint:
        name: Lint
        runs-on: ubuntu-latest

        steps:
            - name: Checkout
              uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1

            - name: Setup PHP
              uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # 2.37.2
              with:
                  php-version: ${{ env.PHP_VERSION }}
                  tools: symfony-cli
                  coverage: none

            - name: Validate Composer files
              run: composer validate --strict --no-check-publish

            - name: Install dependencies
              uses: ramsey/composer-install@65e4f84970763564f46a70b8a54b90d033b3bdda # 4.0.0
              with:
                  require-lock-file: true

            - name: Lint container
              run: php bin/console lint:container --env=prod
<?php if ($has_twig): ?>

            - name: Lint Twig
              run: php bin/console lint:twig templates --env=prod
<?php endif; ?>

            - name: Lint YAML
              run: php bin/console lint:yaml config --parse-tags
<?php if ($has_xliff_translations): ?>

            - name: Lint XLIFF translations
              run: php bin/console lint:xliff translations --format=github
<?php endif; ?>
<?php if ($has_yaml_translations): ?>

            - name: Lint YAML translations
              run: php bin/console lint:yaml translations --format=github
<?php endif; ?>
<?php if ($has_translation): ?>

            - name: Lint translations
              run: php bin/console lint:translations
<?php endif; ?>

            - name: Symfony diagnostics
              run: symfony lsp:check --format=github
<?php if ($has_asset_mapper): ?>

            - name: Compile assets
              run: php bin/console asset-map:compile --env=prod
<?php endif; ?>

            - name: Warm production cache
              run: APP_DEBUG=0 php bin/console cache:warmup --env=prod
<?php if ($has_php_cs_fixer): ?>

    php-cs-fixer:
        name: PHP CS Fixer
        runs-on: ubuntu-latest

        steps:
            - name: Checkout
              uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1

            - name: Setup PHP
              uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # 2.37.2
              with:
                  php-version: ${{ env.PHP_VERSION }}
                  coverage: none

            - name: Install dependencies
              uses: ramsey/composer-install@65e4f84970763564f46a70b8a54b90d033b3bdda # 4.0.0
              with:
                  require-lock-file: true

            - name: Check code style
              run: vendor/bin/php-cs-fixer check --diff --using-cache=no
<?php endif; ?>
<?php if ($has_twig_cs_fixer): ?>

    twig-cs-fixer:
        name: Twig CS Fixer
        runs-on: ubuntu-latest

        steps:
            - name: Checkout
              uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1

            - name: Setup PHP
              uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # 2.37.2
              with:
                  php-version: ${{ env.PHP_VERSION }}
                  coverage: none

            - name: Install dependencies
              uses: ramsey/composer-install@65e4f84970763564f46a70b8a54b90d033b3bdda # 4.0.0
              with:
                  require-lock-file: true

            - name: Check Twig templates
              run: vendor/bin/twig-cs-fixer lint templates --report=github
<?php endif; ?>
<?php if ($has_phpstan): ?>

    phpstan:
        name: PHPStan
        runs-on: ubuntu-latest
        env:
            APP_ENV: dev

        steps:
            - name: Checkout
              uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1

            - name: Setup PHP
              uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240 # 2.37.2
              with:
                  php-version: ${{ env.PHP_VERSION }}
                  coverage: none

            - name: Install dependencies
              uses: ramsey/composer-install@65e4f84970763564f46a70b8a54b90d033b3bdda # 4.0.0
              with:
                  require-lock-file: true

            - name: Restore PHPStan result cache
              uses: actions/cache/restore@55cc8345863c7cc4c66a329aec7e433d2d1c52a9 # v6.1.0
              with:
                  path: /tmp/phpstan
                  key: phpstan-result-cache-v1-${{ env.PHP_VERSION }}-${{ github.run_id }}
                  restore-keys: |
                      phpstan-result-cache-v1-${{ env.PHP_VERSION }}-

            - name: Run PHPStan
              run: vendor/bin/phpstan analyse --no-progress

            - name: Save PHPStan result cache
              uses: actions/cache/save@55cc8345863c7cc4c66a329aec7e433d2d1c52a9 # v6.1.0
              if: ${{ !cancelled() }}
              with:
                  path: /tmp/phpstan
                  key: phpstan-result-cache-v1-${{ env.PHP_VERSION }}-${{ github.run_id }}
<?php endif; ?>
