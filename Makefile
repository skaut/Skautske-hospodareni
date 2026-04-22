COMPOSE     = docker compose -f docker/docker-compose.yml
RUN_PHP_DEV = $(COMPOSE) run --rm -T php
RUN_PHP_TEST = $(COMPOSE) run --rm -T php-test
TEST ?=
TEST_ARGS = $(if $(strip $(TEST)),$(TEST),)

.PHONY: help up down enter init test-enter test-init test-services \
        test-unit test-integration test-acceptance test-mapping ci-acceptance \
        check-phpstan check-cs check-cs-check check-latte \
        fix ci

# ── Nápověda ──────────────────────────────────────────────────
help: ## Zobrazí tuto nápovědu
	@grep -E '^[a-zA-Z_-]+:.*## ' Makefile | awk -F ':[^#]*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Dev helpers (vyžadují běžící kontejnery) ──────────────────
up: down ## Restartuje dev kontejnery
	$(COMPOSE) up -d --force-recreate

down: ## Zastaví dev kontejnery
	$(COMPOSE) down --remove-orphans

enter: ## Shell do PHP kontejneru
	@docker exec -it $$($(COMPOSE) ps -q php) bash

init: ## Inicializace aplikace (composer app-init)
	$(RUN_PHP_DEV) composer app-init

test-enter: ## Shell do test PHP kontejneru
	@docker exec -it $$($(COMPOSE) ps -q php-test) bash

test-init: ## Inicializace testovací aplikace
	$(RUN_PHP_TEST) composer app-init

test-services: ## Start test DB container
	$(COMPOSE) up -d mysql-test

# ── Testy ─────────────────────────────────────────────────────
test-unit: ## Unit testy (volitelně TEST=tests/unit/FooTest.php)
	$(RUN_PHP_TEST) vendor/bin/codecept run unit $(TEST_ARGS)

test-integration: ## Integrační testy (volitelně TEST=tests/integration/FooTest.php)
	$(MAKE) test-services
	$(RUN_PHP_TEST) vendor/bin/codecept run integration $(TEST_ARGS)

test-acceptance: ## Akceptační testy lokálně s viditelným Selenium preview (volitelně TEST=tests/acceptance/FooCest.php:testBar)
	$(COMPOSE) up -d traefik mysql-test selenium nginx php-test
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T selenium wget -q -O - http://localhost:4444/wd/hub/status 2>/dev/null | grep -q '"ready":[[:space:]]*true'; then \
			break; \
		fi; \
		echo "Waiting for Selenium... ($$i/30)"; \
		sleep 2; \
	done
	$(RUN_PHP_TEST) composer tests:acceptance:init; \
	$(RUN_PHP_TEST) vendor/bin/codecept run acceptance -vv $(TEST_ARGS); \
	status=$$?; \
	$(COMPOSE) stop selenium nginx; \
	exit $$status

test-mapping: ## Validace DB schématu vs migrace
	$(MAKE) test-services
	$(RUN_PHP_TEST) composer validate-mapping

ci-acceptance:
	$(COMPOSE) up -d traefik mysql-test selenium nginx php-test
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T selenium wget -q -O - http://localhost:4444/wd/hub/status 2>/dev/null | grep -q '"ready":[[:space:]]*true'; then \
			break; \
		fi; \
		echo "Waiting for Selenium... ($$i/30)"; \
		sleep 2; \
	done
	@for i in $$(seq 1 30); do \
		if $(COMPOSE) exec -T php-test sh -lc "wget -S -O /dev/null http://moje-hospodareni.cz/ 2>&1 | grep -q 'HTTP/[0-9.][0-9.]* 200'"; then \
			break; \
		fi; \
		echo "Waiting for application... ($$i/30)"; \
		sleep 2; \
	done
	$(RUN_PHP_TEST) composer tests:acceptance:init; \
	$(RUN_PHP_TEST) vendor/bin/codecept run acceptance --env ci -vv $(TEST_ARGS); \
	status=$$?; \
	$(COMPOSE) stop selenium nginx; \
	exit $$status

# ── Kontroly kódu ────────────────────────────────────────────
check-phpstan: ## PHPStan analýza
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

# ── Kompletní CI pipeline ────────────────────────────────────
ci: ## Kompletní pipeline (jako GitHub Actions)
	@echo ""
	@echo "\033[1;35m══════ Unit tests ══════\033[0m"
	$(MAKE) test-unit
	@echo ""
	@echo "\033[1;35m══════ Integration tests ══════\033[0m"
	$(MAKE) test-integration
	@echo ""
	@echo "\033[1;35m══════ Acceptance tests ══════\033[0m"
	$(MAKE) ci-acceptance
	@echo ""
	@echo "\033[1;35m══════ PHPStan ══════\033[0m"
	$(MAKE) check-phpstan
	@echo ""
	@echo "\033[1;35m══════ Coding standard ══════\033[0m"
	$(MAKE) check-cs-check
	@echo ""
	@echo "\033[1;35m══════ Latte lint ══════\033[0m"
	$(MAKE) check-latte
	@echo ""
	@echo "\033[1;35m══════ Mapping validation ══════\033[0m"
	$(MAKE) test-mapping
	@echo ""
	@echo "\033[1;32m══════ ALL PASSED ✓ ══════\033[0m"
