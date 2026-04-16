include docker/.env
include .env.local
-include .env

CONTAINER_PHP=$(COMPOSE_PROJECT_NAME)-php-1
CONTAINER_PHP_TEST=$(COMPOSE_PROJECT_NAME)-php-test-1
CONTAINER_DB=$(COMPOSE_PROJECT_NAME)-mysql-1
CONTAINER_DB_TEST=$(COMPOSE_PROJECT_NAME)-mysql-test-1

CONSOLE?=
WITH_FIXTURES?=0
FIXTURES_ARGS?=--append --no-interaction

.DEFAULT_GOAL := help

.PHONY: help up up-test down down-test ensure-test enter init init-fixtures tests-all latte-lint tests-unit tests-integration tests-acceptance static-analysis coding-standard fix clean build

help: ## Zobrazí tuto nápovědu
	@grep -E '^[a-zA-Z_-]+:.*## ' Makefile | awk -F ':[^#]*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: down ## Restartuje dev kontejnery
	docker compose ${COMPOSE_FILE} up -d --force-recreate

up-test: down ## Restartuje dev + test kontejnery
	docker compose ${COMPOSE_FILE} --profile test up -d --force-recreate

down: ## Zastaví a odstraní kontejnery
	docker compose ${COMPOSE_FILE} down --remove-orphans

down-test: down ## Alias pro down (zastaví vše včetně test profilu)

ensure-test: ## Zajistí běh test kontejnerů (idempotentní)
	@docker compose ${COMPOSE_FILE} --profile test up -d

enter: ## Otevře bash v PHP kontejneru
	@docker exec -it ${CONTAINER_PHP} bash

init: ## Inicializuje aplikaci (WITH_FIXTURES=1 pro fixtures)
	docker exec $(CONTAINER_PHP) composer run app-init
ifneq ($(WITH_FIXTURES),0)
	docker exec $(CONTAINER_PHP) php bin/console doctrine:fixtures:load $(FIXTURES_ARGS)
endif

init-fixtures: ## Inicializuje aplikaci včetně fixtures
	$(MAKE) init WITH_FIXTURES=1

tests-all: ensure-test ## Spustí všechny testy
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests

latte-lint: ## Zkontroluje Latte šablony
	docker exec -it ${CONTAINER_PHP} composer run latte-lint

tests-unit: ensure-test ## Spustí unit testy
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:unit

tests-integration: ensure-test ## Spustí integrační testy
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:integration

tests-acceptance: ensure-test ## Spustí akceptační testy
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:acceptance

static-analysis: ## Spustí PHPStan statickou analýzu
	docker exec -it ${CONTAINER_PHP} composer run static-analysis

coding-standard: ## Zkontroluje coding standard
	docker exec -it ${CONTAINER_PHP} composer run coding-standard

fix: ## Opraví coding standard + spustí statickou analýzu
	docker exec -it ${CONTAINER_PHP} composer run coding-standard
	docker exec -it ${CONTAINER_PHP} composer run static-analysis

clean: ## Smaže temp, webtemp, node_modules, cache
	docker exec -it ${CONTAINER_PHP} rm -rf temp/* www/webtemp/* node_modules frontend/.cache || true

build: ## Sestaví frontend assets
	docker exec -it ${CONTAINER_PHP} yarn build
