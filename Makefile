COMPOSE     = docker compose -f docker/docker-compose.yml
RUN_PHP     = $(COMPOSE) run --rm -T -e DB_TEST=true php-test
CONFIG_LOCAL = app/config/config.local.neon
CONFIG_CI    = app/config/config.ci.local.neon
CONFIG_BAK   = app/config/config.local.neon.bak

.PHONY: help up down enter init \
        unit integration acceptance phpstan cs cs-check latte mapping fix \
        ci _ci-config-swap _ci-config-restore

# ── Nápověda ──────────────────────────────────────────────────
help: ## Zobrazí tuto nápovědu
	@grep -E '^[a-zA-Z_-]+:.*## ' Makefile | awk -F ':[^#]*## ' '{printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Dev helpers (vyžadují běžící kontejnery) ──────────────────
up: down ## Restartuje dev kontejnery
	$(COMPOSE) up -d --force-recreate

down: ## Zastaví dev kontejnery
	$(COMPOSE) down --remove-orphans

enter: ## Shell do PHP kontejneru
	@docker exec -it $$($(COMPOSE) ps -q php-test) bash

init: ## Inicializace aplikace (composer app-init)
	$(RUN_PHP) composer app-init

# ── Testy ─────────────────────────────────────────────────────
unit: ## Unit testy
	$(RUN_PHP) composer tests:unit

integration: ## Integrační testy
	$(RUN_PHP) composer tests:integration

acceptance: _ci-config-swap ## Akceptační testy (selenium + headless)
	$(COMPOSE) up -d mysql-test selenium nginx php-test
	$(RUN_PHP) composer tests:acceptance; \
	status=$$?; \
	$(MAKE) --no-print-directory _ci-config-restore; \
	$(COMPOSE) stop selenium nginx; \
	exit $$status

# ── Kontroly kódu ────────────────────────────────────────────
phpstan: ## PHPStan analýza
	$(RUN_PHP) sh -c "vendor/bin/codecept build && composer static-analysis"

cs: ## Coding standard (opraví)
	$(RUN_PHP) composer coding-standard

cs-check: ## Coding standard (dry-run pro CI)
	$(RUN_PHP) composer coding-standard-ci

latte: _ci-config-swap ## Latte lint
	$(RUN_PHP) sh -c "DEVELOPMENT_MACHINE=true composer lint"; \
	status=$$?; \
	$(MAKE) --no-print-directory _ci-config-restore; \
	exit $$status

mapping: _ci-config-swap ## Validace DB schématu vs migrace
	$(RUN_PHP) sh -c "DEVELOPMENT_MACHINE=true composer validate-mapping"; \
	status=$$?; \
	$(MAKE) --no-print-directory _ci-config-restore; \
	exit $$status

fix: ## Coding standard + PHPStan
	$(RUN_PHP) composer coding-standard
	$(RUN_PHP) sh -c "vendor/bin/codecept build && composer static-analysis"

# ── Config swap helper ────────────────────────────────────────
_ci-config-swap:
	@if [ -f $(CONFIG_LOCAL) ]; then cp $(CONFIG_LOCAL) $(CONFIG_BAK); fi
	cp $(CONFIG_CI) $(CONFIG_LOCAL)

_ci-config-restore:
	@if [ -f $(CONFIG_BAK) ]; then mv $(CONFIG_BAK) $(CONFIG_LOCAL); \
	else rm -f $(CONFIG_LOCAL); fi

# ── Kompletní CI pipeline ────────────────────────────────────
ci: ## Kompletní pipeline (jako GitHub Actions)
	@echo ""
	@echo "\033[1;35m══════ Unit tests ══════\033[0m"
	$(MAKE) unit
	@echo ""
	@echo "\033[1;35m══════ Integration tests ══════\033[0m"
	$(MAKE) integration
	@echo ""
	@echo "\033[1;35m══════ Acceptance tests ══════\033[0m"
	$(MAKE) acceptance
	@echo ""
	@echo "\033[1;35m══════ PHPStan ══════\033[0m"
	$(MAKE) phpstan
	@echo ""
	@echo "\033[1;35m══════ Coding standard ══════\033[0m"
	$(MAKE) cs-check
	@echo ""
	@echo "\033[1;35m══════ Latte lint ══════\033[0m"
	$(MAKE) latte
	@echo ""
	@echo "\033[1;35m══════ Mapping validation ══════\033[0m"
	$(MAKE) mapping
	@echo ""
	@echo "\033[1;32m══════ ALL PASSED ✓ ══════\033[0m"