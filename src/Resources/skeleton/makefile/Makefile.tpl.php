.DEFAULT_GOAL := help

SYMFONY_BIN = symfony
<?php if ($hasEncore): ?>
NODE_PACKAGES_MANAGER = <?= $useYarn ? 'yarn' : 'npm' ?>

<?php endif; ?>

COMPOSER = $(SYMFONY_BIN) composer
PHPUNIT = $(SYMFONY_BIN) php bin/phpunit
SYMFONY = $(SYMFONY_BIN) console

<?php if ($hasDoctrine): ?>
##
## Database
<?php if ($hasDoctrineFixturesBundle): ?>
.PHONY: db db-reset db-cache db-validate fixtures

db: vendor db-reset fixtures ## Reset database and load fixtures
<?php else: ?>
.PHONY: db db-reset db-cache db-validate

db: vendor db-reset ## Reset database
<?php endif; ?>

db-cache: vendor ## Clear doctrine database cache
<?="\t"?>@$(SYMFONY) doctrine:cache:clear-metadata
<?="\t"?>@$(SYMFONY) doctrine:cache:clear-query
<?="\t"?>@$(SYMFONY) doctrine:cache:clear-result
<?="\t"?>@echo "Cleared doctrine cache"

db-reset: vendor ## Reset database
<?php if ($useSqlite): ?>
<?="\t"?>@-$(SYMFONY) doctrine:database:drop --force
<?="\t"?>@-$(SYMFONY) doctrine:database:create
<?php else: ?>
<?="\t"?>@-$(SYMFONY) doctrine:database:drop --if-exists --force
<?="\t"?>@-$(SYMFONY) doctrine:database:create --if-not-exists
<?php endif; ?>
<?="\t"?>@$(SYMFONY) doctrine:schema:update --force

db-validate: vendor ## Checks doctrine's mapping configurations are valid
<?="\t"?>@$(SYMFONY) doctrine:schema:validate --skip-sync -vvv --no-interaction

<?php if ($hasDoctrineFixturesBundle): ?>
fixtures: vendor ## Load fixtures - requires database with tables
<?="\t"?>@$(SYMFONY) doctrine:fixtures:load --no-interaction

<?php endif; ?>
<?php endif; ?>

##
## Linting
<?php if ($hasTwig): ?>
.PHONY: lint lint-container lint-twig lint-yaml
<?php else: ?>
.PHONY: lint lint-container lint-yaml
<?php endif; ?>

lint: vendor ## Run all lint commands
<?php if ($hasTwig): ?>
<?="\t"?>@make -j lint-container lint-twig lint-yaml
<?php else: ?>
<?="\t"?>@make -j lint-container lint-yaml
<?php endif; ?>

lint-container: vendor ## Checks the services defined in the container
<?="\t"?>@$(SYMFONY) lint:container

<?php if ($hasTwig): ?>
lint-twig: vendor ## Check twig syntax in /templates folder (prod environment)
<?="\t"?>@$(SYMFONY) lint:twig templates -e prod
<?php endif; ?>

lint-yaml: vendor ## Check yaml syntax in /config folder
<?="\t"?>@$(SYMFONY) lint:yaml config
<?php if ($hasEncore): ?>


##
## Node.js
.PHONY: assets build watch

<?php if ($useYarn): ?>
yarn.lock: package.json
<?="\t"?>@$(NODE_PACKAGES_MANAGER) upgrade

node_modules: yarn.lock ## Install node packages
<?="\t"?>@$(NODE_PACKAGES_MANAGER) install
<?php else: ?>
package-lock.json: package.json
<?="\t"?>@$(NODE_PACKAGES_MANAGER) update

node_modules: package-lock.json ## Install node packages
<?="\t"?>@$(NODE_PACKAGES_MANAGER) install
<?php endif; ?>

assets: node_modules ## Run Webpack Encore to compile development assets
<?="\t"?>@$(NODE_PACKAGES_MANAGER) run dev

build: node_modules ## Run Webpack Encore to compile production assets
<?="\t"?>@$(NODE_PACKAGES_MANAGER) run build

watch: node_modules ## Recompile assets automatically when files change
<?="\t"?>@$(NODE_PACKAGES_MANAGER) run watch
<?php endif; ?>


##
## PHP
composer.lock: composer.json
<?="\t"?>@$(COMPOSER) update

vendor: composer.lock ## Install dependencies in /vendor folder
<?="\t"?>@$(COMPOSER) install --no-progress


##
## Project
.PHONY: install update cache-clear cache-warmup ci clean purge reset start

<?php if ($hasDoctrine && $hasEncore): ?>
install: ## Install project dependencies
<?="\t"?>@make -j db assets

update: vendor node_modules ## Update project dependencies
<?="\t"?>@$(COMPOSER) update
<?="\t"?>@$(NODE_PACKAGES_MANAGER) <?= $useYarn ? 'upgrade' : 'update' ?>
<?php elseif ($hasDoctrine): ?>
install: db ## Install project dependencies

update: vendor ## Update project dependencies
<?="\t"?>@$(COMPOSER) update
<?php elseif ($hasEncore): ?>
install: ## Install project dependencies
<?="\t"?>@make -j vendor assets

update: vendor node_modules ## Update project dependencies
<?="\t"?>@$(COMPOSER) update
<?="\t"?>@$(NODE_PACKAGES_MANAGER) <?= $useYarn ? 'upgrade' : 'update' ?>
<?php else: ?>
install: vendor ## Install project dependencies

update: vendor ## Update project dependencies
<?="\t"?>@$(COMPOSER) update

<?php endif; ?>

cache-clear: vendor ## Clear cache for current environment
<?="\t"?>@$(SYMFONY) cache:clear --no-warmup

cache-warmup: vendor cache-clear ## Clear and warm up cache for current environment
<?="\t"?>@$(SYMFONY) cache:warmup

<?php if ($hasDoctrine): ?>
ci: db-validate lint security tests ## Continuous integration
<?php else: ?>
ci: lint security tests ## Continuous integration
<?php endif; ?>

clean: purge ## Delete all dependencies
<?="\t"?>@echo -n "This will delete var, vendor, node_modules and public/build folders, are you sure? [y/N] " && read ans && [ $${ans:-N} = y ]
<?="\t"?>@rm -rf var vendor node_modules public/build
<?="\t"?>@echo "Var, vendor, node_modules and public/build folders have been deleted !"

purge: ## Purge cache and logs
<?="\t"?>@echo -n "This will delete var/cache var/log folders, are you sure? [y/N] " && read ans && [ $${ans:-N} = y ]
<?="\t"?>@rm -rf var/cache/* var/log/*
<?="\t"?>@echo "Cache and logs have been deleted !"

reset: unserve clean install ## Reset project

start: install serve ## Install project dependencies and launch symfony web server


##
## Symfony binary
.PHONY: serve unserve security

serve: ## Run symfony web server in the background
<?="\t"?>@$(SYMFONY_BIN) serve --daemon --no-tls

unserve: ## Stop symfony web server
<?="\t"?>@$(SYMFONY_BIN) server:stop

security: vendor ## Check packages vulnerabilities (using composer.lock)
<?="\t"?>@$(SYMFONY_BIN) check:security


##
## Tests
.PHONY: tests

tests: vendor ## Run tests
<?="\t"?>@$(PHPUNIT)


##
## Help
.PHONY: help

help: ## List of all commands
<?="\t"?>@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
