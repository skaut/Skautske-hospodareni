include .env.local
-include .env

CONTAINER_PHP=hskauting-php-1
CONTAINER_PHP_TEST=hskauting-php-test-1
CONTAINER_DB=hskauting-mysql-1
CONTAINER_DB_TEST=hskauting-mysql-test-1

CONSOLE?=
WITH_FIXTURES?=0
FIXTURES_ARGS?=--append --no-interaction

up: down
	docker compose ${COMPOSE_FILE} up -d --force-recreate

down:
	docker compose ${COMPOSE_FILE} down --remove-orphans

enter:
	@docker exec -it ${CONTAINER_PHP} bash

init:
	docker exec $(CONTAINER_PHP) composer run app-init
ifneq ($(WITH_FIXTURES),0)
	docker exec $(CONTAINER_PHP) php bin/console doctrine:fixtures:load $(FIXTURES_ARGS)
endif

init-fixtures:
	$(MAKE) init WITH_FIXTURES=1

tests-all:
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests

latte-lint:
	docker exec -it ${CONTAINER_PHP} composer run latte-lint

tests-unit:
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:unit

tests-integration:
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:integration

tests-acceptance:
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests:acceptance

static-analysis:
	docker exec -it ${CONTAINER_PHP} composer run static-analysis

coding-standard:
	docker exec -it ${CONTAINER_PHP} composer run coding-standard

fix:
	docker exec -it ${CONTAINER_PHP} composer run coding-standard
	docker exec -it ${CONTAINER_PHP} composer run static-analysis

clean:
	docker exec -it ${CONTAINER_PHP} rm -rf temp/* www/webtemp/* node_modules frontend/.cache || true

build:
	docker exec -it ${CONTAINER_PHP} yarn build
