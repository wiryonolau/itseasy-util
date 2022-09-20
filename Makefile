# HELP
# This will output the help for each task
.PHONY: help

help: ## This help.
    @awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := help

THIS_FILE := $(lastword $(MAKEFILE_LIST))
PHP_VERSION ?= "7.4"
PROJECT_NAME := "$$(basename `pwd` | cut -d. -f1 )"

%:
	@echo ""
all:
	@echo ""
run:
	docker run --rm -it \
        -v $$(pwd):/srv/${PROJECT_NAME} \
		-w /srv/${PROJECT_NAME} \
		--user "$$(id -u):$$(id -g)" \
        --name ${PROJECT_NAME}_cli \
    php:$(PHP_VERSION)-cli-ext $(filter-out $@,$(MAKECMDGOALS))
build:
	@if [ "$$(docker images -q php:${PHP_VERSION}-cli-ext 2>/dev/null)" = "" ]; then \
        docker build -t php:${PHP_VERSION}-cli-ext -f docker/php-cli-ext/Dockerfile .; \
    fi
unittest:
	$(MAKE) build
	docker run --rm -it \
        -v $$(pwd):/srv/${PROJECT_NAME} \
        -w /srv/${PROJECT_NAME} \
        --user "$$(id -u):$$(id -g)" \
    php:$(PHP_VERSION)-cli-ext vendor/bin/phpunit --verbose --debug tests  
composer-install:
	docker run --rm -it \
        -v $$(pwd):/srv/${PROJECT_NAME} \
        -w /srv/${PROJECT_NAME} \
        -e COMPOSER_HOME="/srv/${PROJECT_NAME}/.composer" \
        --user $$(id -u):$$(id -g) \
    composer composer install --no-plugins --no-scripts --prefer-dist -v
composer-update:
	docker run --rm -it \
        -v $$(pwd):/srv/${PROJECT_NAME} \
        -w /srv/${PROJECT_NAME} \
        -e COMPOSER_HOME="/srv/${PROJECT_NAME}/.composer" \
        --user $$(id -u):$$(id -g) \
	composer composer update --no-plugins --no-scripts  --prefer-dist -v
composer:
	docker run --rm -it \
        -v $$(pwd):/srv/${PROJECT_NAME} \
        -w /srv/${PROJECT_NAME} \
        -e COMPOSER_HOME="/srv/${PROJECT_NAME}/.composer" \
        --user $$(id -u):$$(id -g) \
    composer composer $(filter-out $@,$(MAKECMDGOALS))
