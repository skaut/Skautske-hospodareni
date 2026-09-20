SHELL := /bin/bash
.DEFAULT_GOAL := help

# Development uses the source checkout. Tests always use the immutable CI images.
export DOCKER_SOCKET ?= $(shell docker context inspect --format '{{.Endpoints.docker.Host}}' 2>/dev/null | sed -n 's|^unix://||p')

DEV_COMPOSE = docker compose -f docker/docker-compose.yml
CI_PROJECT_NAME ?= hskauting-ci
ifneq ($(strip $(COMPOSE_PROJECT_NAME)),)
CI_PROJECT_NAME := $(COMPOSE_PROJECT_NAME)
endif
CI_COMPOSE = COMPOSE_PROJECT_NAME=$(CI_PROJECT_NAME) docker compose -f docker/docker-compose.yml -f docker/docker-compose.ci.yml

RUN_PHP_DEV = $(DEV_COMPOSE) run --rm -T --no-deps --entrypoint '' --user docker php
RUN_PHP_CI = $(CI_COMPOSE) run --rm -T --no-deps --entrypoint '' --user docker php-test
COMPOSER_ROOT_VERSION ?= dev-master
COMPOSER_ENV = env COMPOSER_ROOT_VERSION=$(COMPOSER_ROOT_VERSION)

DEV_SERVICES = traefik php php-xdebug nginx mysql adminer gotenberg
CI_PHP_SERVICES = mysql-test php-test gotenberg
CI_ACCEPTANCE_SERVICES = mysql-test php-test nginx selenium gotenberg
CI_ARTIFACT_DIR ?= artifacts/$(CI_PROJECT_NAME)
CI_INPUTS_PREPARED ?= 0

TEST ?=
TEST_ARGS = $(if $(strip $(TEST)),$(TEST),)

.PHONY: help build up down restart ps logs enter enter-xdebug clean-cache fixtures \
	composer-install composer-update init ci-prepare ci-clean ci-image-clean \
	test-unit test-integration test-coverage test-acceptance ci-acceptance \
	test-mapping check-phpstan check-cs check-cs-check check-latte fix ci acceptance-run

define print_section
	@printf "\n\033[1;35m══════ %s ══════\033[0m\n" "$(1)"
endef

define wait_for_mysql_test
	ready=false; for i in $$(seq 1 30); do \
		if $(CI_COMPOSE) exec -T mysql-test sh -lc 'mysqladmin ping -h 127.0.0.1 -uroot -p"$$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1; then ready=true; break; fi; \
		echo "Waiting for mysql-test... ($$i/30)"; sleep 2; \
	done; \
	if [ "$$ready" != true ]; then echo "mysql-test did not become ready in time."; exit 1; fi
endef

define wait_for_selenium
	ready=false; for i in $$(seq 1 30); do \
		if $(CI_COMPOSE) exec -T selenium wget -q -O - http://localhost:4444/wd/hub/status 2>/dev/null | grep -q '"ready":[[:space:]]*true'; then ready=true; break; fi; \
		echo "Waiting for Selenium... ($$i/30)"; sleep 2; \
	done; \
	if [ "$$ready" != true ]; then echo "Selenium did not become ready in time."; exit 1; fi
endef

define wait_for_application
	ready=false; last_response=''; for i in $$(seq 1 30); do \
		last_response="$$($(CI_COMPOSE) exec -T selenium sh -lc 'wget --timeout=2 --tries=1 -S --header="Cookie: SELENIUM=SELENIUM" -O /dev/null http://moje-hospodareni.cz/ 2>&1')"; \
		if printf '%s\n' "$$last_response" | grep -q 'HTTP/[0-9.][0-9.]* 200'; then ready=true; break; fi; \
		echo "Waiting for application... ($$i/30)"; sleep 2; \
	done; \
	if [ "$$ready" != true ]; then echo "Application did not become ready in time."; printf '%s\n' "$$last_response"; exit 1; fi
endef

define wait_for_skautis_dns
	ready=false; for i in $$(seq 1 30); do \
		if $(CI_COMPOSE) exec -T php-test php -r 'exit(gethostbynamel("test-is.skaut.cz") === false ? 1 : 0);' >/dev/null 2>&1; then ready=true; break; fi; \
		echo "Waiting for SkautIS DNS... ($$i/30)"; sleep 2; \
	done; \
	if [ "$$ready" != true ]; then echo "SkautIS test host DNS did not become available in time."; exit 1; fi
endef

define ci_collect_failure
	mkdir -p "$(CI_ARTIFACT_DIR)"; \
	$(CI_COMPOSE) ps --all > "$(CI_ARTIFACT_DIR)/compose-ps.txt" || true; \
	$(CI_COMPOSE) logs --no-color > "$(CI_ARTIFACT_DIR)/compose-logs.txt" || true; \
	php_test_container="$$($(CI_COMPOSE) ps -aq php-test)"; \
	if [ -n "$$php_test_container" ]; then docker inspect --format '{{json .State}}' "$$php_test_container" > "$(CI_ARTIFACT_DIR)/php-test-state.json" || true; fi; \
	$(CI_COMPOSE) run --rm -T --no-deps --entrypoint '' --user docker php-test \
		sh -lc 'id; find /app/log -maxdepth 1 -type f -printf "%m %u:%g %s %p\\n"; tar -czf - -C /app log tests/_output' \
		> "$(CI_ARTIFACT_DIR)/runtime.tar.gz" 2> "$(CI_ARTIFACT_DIR)/runtime.err" || true
endef

define ci_run
	@set -e; \
	cleanup_ci() { $(CI_COMPOSE) down --remove-orphans -v >/dev/null 2>&1 || true; }; \
	collect_failure() { $(call ci_collect_failure); }; \
	cleanup_ci; \
	trap 'status=$$?; if [ "$$status" -ne 0 ]; then collect_failure; fi; cleanup_ci; exit "$$status"' EXIT; \
	$(CI_COMPOSE) build php-test; \
	if [ -n "$(filter nginx,$(1))" ]; then $(CI_COMPOSE) build nginx; fi; \
	$(CI_COMPOSE) up -d $(1); \
	$(2)
endef

help: ## Zobrazí kompletní seznam příkazů
	@grep -E '^[a-zA-Z0-9_-]+:.*## ' Makefile | awk -F ':[^#]*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Sestaví Docker image pro rootless vývoj
	$(DEV_COMPOSE) build php php-xdebug nginx

up: ## Spustí vývojový stack
	$(DEV_COMPOSE) up -d $(DEV_SERVICES)

down: ## Zastaví vývojový stack
	$(DEV_COMPOSE) down --remove-orphans

restart: down up ## Restartuje vývojový stack

ps: ## Vypíše vývojové služby
	$(DEV_COMPOSE) ps

logs: ## Stream logů vývojového stacku
	$(DEV_COMPOSE) logs -f --tail=200

enter: ## Shell do vývojového PHP kontejneru
	$(DEV_COMPOSE) exec -u docker -it php bash

enter-xdebug: ## Shell do vývojového Xdebug kontejneru
	$(DEV_COMPOSE) exec -u docker -it php-xdebug bash

clean-cache: ## Vyčistí vývojovou aplikační cache
	$(RUN_PHP_DEV) bin/console app:cache:purge

composer-install: ## Nainstaluje PHP závislosti ve vývojovém checkoutu
	$(RUN_PHP_DEV) $(COMPOSER_ENV) composer install --no-interaction

composer-update: ## Aktualizuje PHP závislosti ve vývojovém checkoutu
	$(RUN_PHP_DEV) $(COMPOSER_ENV) composer update

init: ## Inicializuje vývojový checkout, databázi a Vite assety
	$(MAKE) build
	$(MAKE) up
	$(RUN_PHP_DEV) $(COMPOSER_ENV) composer app-init
	$(MAKE) fixtures

fixtures: ## Načte vývojová fixture data
	$(RUN_PHP_DEV) bin/console doctrine:fixtures:load --no-interaction

ci-prepare: ## Ověří Composer vstupy pro CI image
ifeq ($(CI_INPUTS_PREPARED),1)
	@test -f vendor/autoload.php
else
	@test -f vendor/autoload.php || (echo "Missing vendor/autoload.php. Run make composer-install first."; exit 1)
endif

ci-clean: ## Odstraní CI kontejnery, síť a volumes aktuálního namespace
	@$(CI_COMPOSE) down --remove-orphans -v || true

ci-image-clean: ## Odstraní CI image aktuálního namespace
	@docker image rm "$(CI_PROJECT_NAME)-php-test" "$(CI_PROJECT_NAME)-nginx" 2>/dev/null || true

test-unit: ci-prepare ## Spustí unit testy v čistém CI prostředí
	$(call ci_run,php-test,$(RUN_PHP_CI) vendor/bin/codecept run unit $(TEST_ARGS))

test-integration: ci-prepare ## Spustí integrační testy v čistém CI prostředí
	$(call ci_run,$(CI_PHP_SERVICES),$(call wait_for_mysql_test); $(RUN_PHP_CI) vendor/bin/codecept run integration $(TEST_ARGS))

test-coverage: ci-prepare ## Vytvoří coverage XML v artifacts/
	$(call ci_run,$(CI_PHP_SERVICES),$(call wait_for_mysql_test); mkdir -p artifacts; $(RUN_PHP_CI) $(COMPOSER_ENV) composer tests-with-coverage; $(RUN_PHP_CI) sh -lc 'cat tests/_output/coverage.xml' > artifacts/coverage.xml)

test-acceptance: acceptance-run ## Spustí acceptance testy v CI režimu

ci-acceptance: acceptance-run ## Kompatibilní název pro CI acceptance testy

acceptance-run: ci-prepare
	$(call ci_run,$(CI_ACCEPTANCE_SERVICES),$(call wait_for_mysql_test); $(call wait_for_selenium); $(RUN_PHP_CI) $(COMPOSER_ENV) composer tests:acceptance:init; $(call wait_for_application); $(call wait_for_skautis_dns); $(RUN_PHP_CI) vendor/bin/codecept run acceptance --env ci -vv $(TEST_ARGS))

test-mapping: ci-prepare ## Ověří mapování a migrace v čistém CI prostředí
	$(call ci_run,$(CI_PHP_SERVICES),$(call wait_for_mysql_test); $(RUN_PHP_CI) $(COMPOSER_ENV) composer validate-mapping)

check-phpstan: ci-prepare ## Spustí PHPStan v čistém CI prostředí
	$(call ci_run,php-test,$(RUN_PHP_CI) sh -lc 'vendor/bin/codecept build && $(COMPOSER_ENV) composer static-analysis')

check-cs: ## Opraví coding standard ve vývojovém checkoutu
	$(RUN_PHP_DEV) $(COMPOSER_ENV) composer coding-standard

check-cs-check: ci-prepare ## Ověří coding standard v čistém CI prostředí
	$(call ci_run,php-test,$(RUN_PHP_CI) $(COMPOSER_ENV) composer coding-standard-ci)

check-latte: ci-prepare ## Spustí Latte lint v čistém CI prostředí
	$(call ci_run,php-test,$(RUN_PHP_CI) $(COMPOSER_ENV) composer lint)

fix: ## Spustí opravitelné kontroly vývojového checkoutu
	$(MAKE) check-cs
	$(MAKE) check-latte
	$(MAKE) check-phpstan

ci: ci-prepare ## Spustí kompletní CI pipeline v lokálním CI prostředí
	$(call print_section,Coding standard)
	$(MAKE) check-cs-check
	$(call print_section,PHPStan)
	$(MAKE) check-phpstan
	$(call print_section,Latte lint)
	$(MAKE) check-latte
	$(call print_section,Unit tests)
	$(MAKE) test-unit
	$(call print_section,Integration tests)
	$(MAKE) test-integration
	$(call print_section,Mapping validation)
	$(MAKE) test-mapping
	$(call print_section,Acceptance tests)
	$(MAKE) test-acceptance
	@printf "\n\033[1;32m══════ ALL PASSED ✓ ══════\033[0m\n"
