SHELL ?= /bin/bash

DOCKER ?= docker
DOCKER_COMPOSE ?= $(DOCKER) compose $(shell test -f .env.local && echo '--env-file .env --env-file .env.local')
export CONTAINER_USER ?= $(shell id -u):$(shell id -g)

RUN ?= $(if $(INSIDE_DEVCONTAINER),,$(DOCKER_COMPOSE) run --rm php)
# thesis/protobuf-known-types, a dev dependency, requires this very package, and Composer cannot tell the version of the
# root package on a detached checkout, as in CI. Move it to the next minor version together with the branch.
COMPOSER_ROOT_VERSION ?= 0.2.x-dev
COMPOSER ?= $(RUN) env COMPOSER_ROOT_VERSION=$(COMPOSER_ROOT_VERSION) composer

##
## Project
## -----

var:
	mkdir var

vendor: composer.json $(wildcard composer.lock)
	@if [ -f vendor/.lowest ]; then $(MAKE) install-lowest; else $(MAKE) install-highest; fi

i: install-highest
install-highest: ## Install highest Composer dependencies
	$(COMPOSER) install
	@rm -f vendor/.lowest
	@touch vendor
.PHONY: i install-highest

install-lowest: ## Install lowest Composer dependencies
	$(COMPOSER) update --prefer-lowest --prefer-stable
	@touch vendor/.lowest
	@touch vendor
.PHONY: install-lowest

up: ## Docker compose up
	$(DOCKER_COMPOSE) up --remove-orphans --build --detach $(ARGS)
.PHONY: up

down: ## Docker compose down
	$(DOCKER_COMPOSE) down --remove-orphans $(ARGS)
.PHONY: down

dc: docker-compose
docker-compose: ## Run docker compose command: `make dc CMD=start`
	$(DOCKER_COMPOSE) $(CMD)
.PHONY: dc docker-compose

c: composer
composer: ## Run Composer command: `make c CMD=start`
	$(COMPOSER) $(CMD)
.PHONY: c composer

run: ## Run a command using the php container: `make run CMD='php --version'`
	$(RUN) $(CMD)
.PHONY: run

t: terminal
terminal: var ## Start a terminal inside the php container
	@$(if $(INSIDE_CONTAINER),echo 'Already inside docker container.'; exit 1,)
	$(DOCKER_COMPOSE) run --rm $(ARGS) php bash
.PHONY: t terminal

rescaffold:
	$(DOCKER) run \
	  --volume .:/project \
	  --user $(CONTAINER_USER) \
	  --interactive --tty --rm \
	  --pull always \
	  ghcr.io/phpyh/scaffolder:latest \
	  --user-name-default "$(shell git config user.name 2>/dev/null || whoami 2>/dev/null)" \
	  --user-email-default "$(shell git config user.email 2>/dev/null)" \
	  --package-project-default "$(shell basename $$(pwd))"
	git add --all 2>/dev/null || true
.PHONY: rescaffold

##
## Tools
## -----

fixer: var ## Fix code style using PHP-CS-Fixer
	$(RUN) php-cs-fixer fix --diff --verbose $(ARGS)
.PHONY: fixer

fixer-check: var ## Check code style using PHP-CS-Fixer
	$(RUN) php-cs-fixer fix --diff --verbose --dry-run $(ARGS)
.PHONY: fixer-check

rector: var ## Fix code style using Rector
	$(RUN) rector process $(ARGS)
.PHONY: rector

rector-check: var ## Check code style using Rector
	$(RUN) rector process --dry-run $(ARGS)
.PHONY: rector-check

phpstan: var vendor ## Analyze code using PHPStan
	$(RUN) phpstan analyze --memory-limit=1G $(ARGS)
.PHONY: phpstan

test: var vendor ## Run tests using PHPUnit
	$(RUN) vendor/bin/phpunit $(ARGS) --colors
.PHONY: test

bench: var vendor ## Run benchmarks: `make bench ARGS='--filter=Packed --tag=baseline'`, then compare with `ARGS='--ref=baseline'`
	$(RUN) vendor/bin/phpbench run --report=protobuf $(ARGS)
.PHONY: bench

# The php image no longer ships Infection: it is installed into var/ on first use.
var/tools/infection/vendor/bin/infection: | var
	mkdir -p var/tools/infection
	$(COMPOSER) --working-dir=var/tools/infection --no-interaction --no-plugins require infection/infection:^0.35

# Infection keeps about 0.1 MB per mutant in memory until the run ends: past 1000 mutants it outgrows the 128M default.
infect: var vendor var/tools/infection/vendor/bin/infection ## Run mutation tests using Infection
	$(RUN) php -d memory_limit=512M var/tools/infection/vendor/bin/infection --show-mutations $(ARGS)
.PHONY: infect

# protoc is not in the php image: the script downloads a pinned release into var/tools on first use.
golden: var ## Regenerate the golden files in tests/testdata/golden with the reference protoc
	$(RUN) sh tests/testdata/golden/generate.sh
.PHONY: golden

# The release of protobuf pinned in tests/tools/protobuf.sh: the conformance test runner is built from its sources.
PROTOBUF_VERSION := $(shell sed -n 's/^protobuf_version=//p' tests/tools/protobuf.sh)
PROTOBUF_SHA256 := $(shell sed -n 's/^protobuf_source_sha256=//p' tests/tools/protobuf.sh)
CONFORMANCE_RUNNER := var/tools/conformance-$(PROTOBUF_VERSION)/conformance_test_runner
CONFORMANCE_RUNNER_IMAGE ?= ghcr.io/thesis-php/protobuf-conformance-runner:$(PROTOBUF_VERSION)
CONFORMANCE_RUNNER_BUILD = $(DOCKER) build --file tests/conformance/Dockerfile \
	--build-arg PROTOBUF_VERSION=$(PROTOBUF_VERSION) --build-arg PROTOBUF_SHA256=$(PROTOBUF_SHA256)

# The runner is not in the release archives: it is built once into var/tools, statically, so it runs in the php container.
$(CONFORMANCE_RUNNER): tests/conformance/Dockerfile tests/conformance/static-jsoncpp.cmake tests/tools/protobuf.sh | var
	$(CONFORMANCE_RUNNER_BUILD) --output type=local,dest=$(dir $@) tests/conformance
	@touch $@

conformance-runner: $(CONFORMANCE_RUNNER) ## Build conformance_test_runner of the pinned protobuf release into var/tools
.PHONY: conformance-runner

conformance-runner-image: ## Build the image of conformance_test_runner to publish as CONFORMANCE_RUNNER_IMAGE
	$(CONFORMANCE_RUNNER_BUILD) --tag $(CONFORMANCE_RUNNER_IMAGE) tests/conformance
.PHONY: conformance-runner-image

# The image holds nothing but the runner: exporting its file system puts the binary where the build would.
conformance-runner-pull: | var ## Copy conformance_test_runner from CONFORMANCE_RUNNER_IMAGE into var/tools instead of building it
	echo 'FROM $(CONFORMANCE_RUNNER_IMAGE)' | $(DOCKER) build --pull --output type=local,dest=$(dir $(CONFORMANCE_RUNNER)) -
	@touch $(CONFORMANCE_RUNNER)
.PHONY: conformance-runner-pull

# protoc and the plugin are not in the php image: the script downloads the pinned releases into var/tools on first use.
conformance-generate: var ## Regenerate the classes of the conformance test messages in tests/conformance/generated
	$(RUN) sh tests/conformance/generate.sh
.PHONY: conformance-generate

conformance: var vendor $(CONFORMANCE_RUNNER) ## Run conformance_test_runner against the library: `make conformance ARGS='--test <name> --debug'`
	$(RUN) $(CONFORMANCE_RUNNER) --enforce_recommended --failure_list tests/conformance/failure_list.txt \
	  --output_dir var $(ARGS) /usr/bin/env php tests/conformance/testee.php
.PHONY: conformance

# google/protobuf is not a dependency of the library: its PHP runtime, the classes protoc generates for it and the image
# with its extension are made on first use. The runtime is the release that goes with the pinned protoc.
export GOOGLE_PROTOBUF_VERSION ?= 5.$(PROTOBUF_VERSION)
COMPARISON_RUN ?= $(DOCKER_COMPOSE) run --rm
# The directories of the image whose settings load the extension of google/protobuf and turn the JIT on.
COMPARISON_INI := /usr/local/etc/php
COMPARISON_BENCH = vendor/bin/phpbench run --config=benchmarks/Comparison/phpbench.json --report=aggregate $(ARGS)

var/tools/google-protobuf/vendor/google/protobuf/composer.json: tests/tools/protobuf.sh | var
	$(DOCKER_COMPOSE) build comparison
	mkdir -p var/tools/google-protobuf
	$(COMPARISON_RUN) comparison composer --working-dir=var/tools/google-protobuf --no-interaction --no-plugins require google/protobuf:$(GOOGLE_PROTOBUF_VERSION)
	@touch $@

bench-compare: var vendor var/tools/google-protobuf/vendor/google/protobuf/composer.json ## Compare the speed of the library with google/protobuf, its PHP runtime and its extension
	$(DOCKER_COMPOSE) build comparison
	mkdir -p var/comparison
	$(COMPARISON_RUN) comparison sh benchmarks/Comparison/generate.sh
	$(COMPARISON_RUN) comparison $(COMPARISON_BENCH) --dump-file=var/comparison/php.xml
	$(COMPARISON_RUN) --env PHP_INI_SCAN_DIR=:$(COMPARISON_INI)/protobuf comparison $(COMPARISON_BENCH) --group=google --dump-file=var/comparison/extension.xml
	$(COMPARISON_RUN) --env PHP_INI_SCAN_DIR=:$(COMPARISON_INI)/jit comparison $(COMPARISON_BENCH) --dump-file=var/comparison/php-jit.xml
	$(COMPARISON_RUN) --env PHP_INI_SCAN_DIR=:$(COMPARISON_INI)/protobuf:$(COMPARISON_INI)/jit comparison $(COMPARISON_BENCH) --group=google --dump-file=var/comparison/extension-jit.xml
	$(COMPARISON_RUN) comparison php benchmarks/Comparison/report.php var/comparison/php.xml var/comparison/extension.xml var/comparison/php-jit.xml var/comparison/extension-jit.xml > var/comparison/report.md
	@cat var/comparison/report.md
.PHONY: bench-compare

deps-analyze: vendor ## Analyze project dependencies using Composer dependency analyser
	$(RUN) composer-dependency-analyser $(ARGS)
.PHONY: deps-analyze

composer-validate: ## Validate composer.json
	$(COMPOSER) validate $(ARGS)
.PHONY: composer-validate

composer-normalize: ## Normalize composer.json
	$(COMPOSER) normalize --no-check-lock --no-update-lock --diff $(ARGS)
.PHONY: composer-normalize

composer-normalize-check: ## Check that composer.json is normalized
	$(COMPOSER) normalize --diff --dry-run $(ARGS)
.PHONY: composer-normalize-check

fix: fixer rector composer-normalize ## Run all fixing recipes
.PHONY: fix

check: fixer-check rector-check composer-validate composer-normalize-check deps-analyze phpstan test  ## Run all project checks
.PHONY: check

# -----------------------

help:
	@awk ' \
		BEGIN {RS=""; FS="\n"} \
		function printCommand(line) { \
			split(line, command, ":.*?## "); \
        	printf "\033[32m%-28s\033[0m %s\n", command[1], command[2]; \
        } \
		/^[0-9a-zA-Z_-]+: [0-9a-zA-Z_-]+\n[0-9a-zA-Z_-]+: .*?##.*$$/ { \
			split($$1, alias, ": "); \
			sub(alias[2] ":", alias[2] " (" alias[1] "):", $$2); \
			printCommand($$2); \
			next; \
		} \
		$$1 ~ /^[0-9a-zA-Z_-]+: .*?##/ { \
			printCommand($$1); \
			next; \
		} \
		/^##(\n##.*)+$$/ { \
			gsub("## ?", "\033[33m", $$0); \
			print $$0; \
			next; \
		} \
	' $(MAKEFILE_LIST) && printf "\033[0m"
.PHONY: help
.DEFAULT_GOAL := help
