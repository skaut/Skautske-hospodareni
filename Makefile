SHELL := /bin/bash

# Docker daemon socket mounted into Traefik for service discovery. Auto-detected
# from the active Docker context (works for both rootful and rootless Docker);
# falls back to the standard rootful socket. Override by exporting DOCKER_SOCKET.
export DOCKER_SOCKET ?= $(shell docker context inspect --format '{{.Endpoints.docker.Host}}' 2>/dev/null | sed -n 's|^unix://||p')

COMPOSE         = docker compose -f docker/docker-compose.yml
RUN_PHP_DEV     = $(COMPOSE) run --rm -T --entrypoint '' --user docker php
RUN_PHP_TEST    = $(COMPOSE) run --rm -T --entrypoint '' --user docker php-test
RUN_PHP_XDEBUG  = $(COMPOSE) run --rm --entrypoint '' --user docker php-xdebug
EXEC_PHP        = docker exec -u docker -it hskauting.app
EXEC_PHP_TEST   = docker exec -u docker -it hskauting.app-test

APP_SERVICES        = traefik php php-xdebug nginx mysql adminer
TEST_SERVICES       = mysql-test php-test
ACCEPTANCE_SERVICES = traefik mysql-test selenium nginx php-test

TEST ?=
TEST_ARGS = $(if $(strip $(TEST)),$(TEST),)

.PHONY: help build up down restart ps logs enter enter-xdebug \
        composer-install composer-update init test-enter test-init \
        test-services test-unit test-integration test-coverage test-acceptance \
        test-mapping ci-acceptance check-phpstan check-cs \
        check-cs-check check-latte fix ci ci-visible

define print_section
	@printf "\n\033[1;35m══════ %s ══════\033[0m\n" "$(1)"
endef

define wait_for_selenium
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T selenium wget -q -O - http://localhost:4444/wd/hub/status 2>/dev/null | grep -q '"ready":[[:space:]]*true'; then \
			exit 0; \
		fi; \
		echo "Waiting for Selenium... ($$i/30)"; \
		sleep 2; \
	done; \
	echo "Selenium did not become ready in time."; \
	exit 1
endef

define wait_for_application
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T selenium sh -lc "wget -S --header='Cookie: SELENIUM=SELENIUM' -O /dev/null http://moje-hospodareni.cz/ 2>&1 | grep -q 'HTTP/[0-9.][0-9.]* 200'"; then \
			exit 0; \
		fi; \
		echo "Waiting for application... ($$i/30)"; \
		sleep 2; \
	done; \
	echo "Application did not become ready in time."; \
	exit 1
endef

define wait_for_mysql_test
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T mysql-test sh -lc 'mysqladmin ping -h 127.0.0.1 -uroot -p"$$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1; then \
			exit 0; \
		fi; \
		echo "Waiting for mysql-test... ($$i/30)"; \
		sleep 2; \
	done; \
	echo "mysql-test did not become ready in time."; \
	exit 1
endef

define reset_writable_dirs
	$(COMPOSE) run --rm -T --no-deps --user docker $(1) sh -c \
		'mkdir -p log uploads temp tests/_output tests/_support/_generated www/webtemp && \
		chmod -R a+rwX log uploads temp tests/_output tests/_support/_generated www/webtemp && \
		if [ -d tests/integration/fixtures ]; then chmod -R a+rwX tests/integration/fixtures; fi'
endef

help: ## Zobrazí tuto nápovědu
	@grep -E '^[a-zA-Z0-9_-]+:.*## ' Makefile | awk -F ':[^#]*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Sestaví Docker image pro lokální vývoj
	$(COMPOSE) build php php-xdebug php-test nginx

up: ## Spustí dev stack
	$(COMPOSE) up -d $(APP_SERVICES)
	$(call reset_writable_dirs,php)

down: ## Zastaví dev stack
	$(COMPOSE) down --remove-orphans

restart: down up ## Restartuje dev stack

ps: ## Vypíše běžící služby
	$(COMPOSE) ps

logs: ## Stream logů všech služeb
	$(COMPOSE) logs -f --tail=200

enter: ## Shell do PHP kontejneru
	$(EXEC_PHP) bash

enter-xdebug: ## Shell do xdebug PHP kontejneru
	$(RUN_PHP_XDEBUG) bash

composer-install: ## composer install uvnitř PHP kontejneru
	$(RUN_PHP_DEV) composer install --no-interaction

composer-update: ## composer update uvnitř PHP kontejneru
	$(RUN_PHP_DEV) composer update

init: ## Inicializace aplikace (composer app-init)
	$(MAKE) build
	$(MAKE) up
	$(RUN_PHP_DEV) composer app-init
	$(call reset_writable_dirs,php)
	@echo ""
	@echo "Aplikace:  http://moje-hospodareni.cz"
	@echo "Adminer:   http://adminer.localhost"
	@echo "Traefik:   http://traefik.localhost"

test-enter: ## Shell do test PHP kontejneru
	$(EXEC_PHP_TEST) bash

test-init: ## Inicializace testovací aplikace
	$(MAKE) test-services
	$(call wait_for_mysql_test)
	$(RUN_PHP_TEST) composer app-init
	$(call reset_writable_dirs,php-test)

test-services: ## Start test DB a test PHP kontejneru
	$(COMPOSE) up -d $(TEST_SERVICES)

test-unit: ## Unit testy (volitelně TEST=tests/unit/FooTest.php)
	$(call reset_writable_dirs,php-test)
	$(RUN_PHP_TEST) vendor/bin/codecept run unit $(TEST_ARGS)

test-integration: ## Integrační testy (volitelně TEST=tests/integration/FooTest.php)
	$(MAKE) test-services
	$(call wait_for_mysql_test)
	$(call reset_writable_dirs,php-test)
	$(RUN_PHP_TEST) vendor/bin/codecept run integration $(TEST_ARGS)

test-coverage: ## Unit + integration testy s coverage XML
	$(MAKE) test-services
	$(call wait_for_mysql_test)
	$(call reset_writable_dirs,php-test)
	$(RUN_PHP_TEST) composer tests-with-coverage

test-acceptance: ## Akceptační testy lokálně s viditelným Selenium preview
	$(COMPOSE) up -d $(ACCEPTANCE_SERVICES)
	$(call wait_for_mysql_test)
	$(call wait_for_selenium)
	$(RUN_PHP_TEST) composer tests:acceptance:init
	$(call reset_writable_dirs,php-test)
	$(RUN_PHP_TEST) vendor/bin/codecept run acceptance -vv $(TEST_ARGS); \
	status=$$?; \
	$(COMPOSE) stop selenium; \
	exit $$status

test-mapping: ## Validace DB schématu vs migrace
	$(MAKE) test-services
	$(call wait_for_mysql_test)
	$(RUN_PHP_TEST) composer validate-mapping

ci-acceptance: ## Akceptační testy v CI režimu
	$(COMPOSE) up -d $(ACCEPTANCE_SERVICES)
	$(call wait_for_mysql_test)
	$(call wait_for_selenium)
	$(RUN_PHP_TEST) composer tests:acceptance:init
	$(call reset_writable_dirs,php-test)
	$(call wait_for_application)
	$(RUN_PHP_TEST) vendor/bin/codecept run acceptance --env ci -vv $(TEST_ARGS); \
	status=$$?; \
	$(COMPOSE) stop selenium nginx; \
	exit $$status

check-phpstan: ## PHPStan analýza
	$(call reset_writable_dirs,php-test)
	$(RUN_PHP_TEST) sh -c "vendor/bin/codecept build && composer static-analysis"

check-cs: ## Coding standard (opraví)
	$(RUN_PHP_TEST) composer coding-standard

check-cs-check: ## Coding standard (dry-run pro CI)
	$(RUN_PHP_TEST) composer coding-standard-ci

check-latte: ## Latte lint
	$(RUN_PHP_TEST) composer lint

fix: ## Opravitelné kontroly bez testů
	$(MAKE) check-cs
	$(MAKE) check-latte
	$(MAKE) check-phpstan

ci: ## Kompletní pipeline (jako GitHub Actions)
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
	$(MAKE) ci-acceptance
	@printf "\n\033[1;32m══════ ALL PASSED ✓ ══════\033[0m\n"

ci-visible: ## Kompletní lokální pipeline s viditelným Selenium preview
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
