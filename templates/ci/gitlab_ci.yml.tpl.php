variables:
    APP_ENV: test
    PHP_VERSION: '<?= $php_version; ?>'
<?php if ($database_url): ?>
    DATABASE_URL: '<?= $database_url; ?>'
<?php endif; ?>

default:
    image: php:${PHP_VERSION}-cli
    before_script:
        - apt-get update -qq && apt-get install -y -qq <?= implode(' ', $system_packages)."\n"; ?>
        - docker-php-ext-install <?= implode(' ', $php_extensions)."\n"; ?>
        # The official PHP images ship without php.ini, so memory_limit defaults to 128M
        - echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini"
        - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        - curl -sS https://get.symfony.com/cli/installer | bash && export PATH="$HOME/.symfony5/bin:$PATH"
        - test -f composer.lock || { echo "composer.lock is missing, commit it to your repository"; exit 1; }
        - composer install --no-interaction --no-progress --prefer-dist
    cache:
        key:
            files:
                - composer.lock
        paths:
            - vendor/

stages:
    - lint
    - test

lint:
    stage: lint
    script:
        - composer validate --strict --no-check-publish
        - php bin/console lint:container --env=prod
<?php if ($has_twig): ?>
        - php bin/console lint:twig templates --env=prod
<?php endif; ?>
        - php bin/console lint:yaml config --parse-tags
<?php if ($has_xliff_translations): ?>
        - php bin/console lint:xliff translations
<?php endif; ?>
<?php if ($has_yaml_translations): ?>
        - php bin/console lint:yaml translations
<?php endif; ?>
<?php if ($has_translation): ?>
        - php bin/console lint:translations
<?php endif; ?>
        - symfony lsp:check
<?php if ($has_asset_mapper): ?>
        - php bin/console asset-map:compile --env=prod
<?php endif; ?>
        - APP_DEBUG=0 php bin/console cache:warmup --env=prod
<?php if ($has_php_cs_fixer): ?>

php-cs-fixer:
    stage: lint
    script:
        - vendor/bin/php-cs-fixer check --diff --using-cache=no
<?php endif; ?>
<?php if ($has_twig_cs_fixer): ?>

twig-cs-fixer:
    stage: lint
    script:
        - vendor/bin/twig-cs-fixer lint templates
<?php endif; ?>
<?php if ($has_phpstan): ?>

phpstan:
    stage: lint
    variables:
        APP_ENV: dev
    script:
        # GitLab only caches paths inside the project directory
        - mkdir -p var/tmp
        - TMPDIR="$CI_PROJECT_DIR/var/tmp" vendor/bin/phpstan analyse --no-progress
    cache:
        - key:
              files:
                  - composer.lock
          paths:
              - vendor/
        - key: phpstan-${CI_COMMIT_REF_SLUG}
          paths:
              - var/tmp/phpstan/
<?php endif; ?>
<?php if ($has_phpunit): ?>

tests:
    stage: test
<?php if ('postgres' === $database_type): ?>
    services:
        - name: postgres:16-alpine
          alias: postgres
          variables:
              POSTGRES_DB: app
              POSTGRES_USER: app
              POSTGRES_PASSWORD: password
<?php elseif ('mysql' === $database_type): ?>
    services:
        - name: mysql:8.0
          alias: mysql
          variables:
              MYSQL_ROOT_PASSWORD: password
              MYSQL_DATABASE: app
<?php elseif ('mariadb' === $database_type): ?>
    services:
        - name: mariadb:10.11
          alias: mariadb
          variables:
              MYSQL_ROOT_PASSWORD: password
              MYSQL_DATABASE: app
<?php endif; ?>
<?php if ($has_doctrine && $has_doctrine_migrations && $database_type): ?>
    script:
<?php if ('sqlite' !== $database_type): ?>
        - php bin/console doctrine:database:create --if-not-exists
<?php endif; ?>
        - php bin/console doctrine:migrations:migrate --no-interaction
        - php bin/console doctrine:schema:validate
        - vendor/bin/phpunit --log-junit var/junit.xml
<?php elseif ($has_doctrine && $database_type): ?>
    script:
        - php bin/console doctrine:schema:validate --skip-sync -vvv --no-interaction
<?php if ('sqlite' !== $database_type): ?>
        - php bin/console doctrine:database:create --if-not-exists
<?php endif; ?>
        - php bin/console doctrine:schema:update --force
        - vendor/bin/phpunit --log-junit var/junit.xml
<?php else: ?>
    script:
        - vendor/bin/phpunit --log-junit var/junit.xml
<?php endif; ?>
    artifacts:
        when: always
        paths:
            - var/junit.xml
        reports:
            junit: var/junit.xml
<?php endif; ?>
