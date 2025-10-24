include .env.local
-include .env

CONTAINER_PHP=hskauting.app
CONTAINER_PHP_TEST=hskauting.app-test
CONTAINER_DB=hskauting.mysql
CONTAINER_DB_TEST=hskauting.mysql-test

CONSOLE?=

up: down
	docker compose ${COMPOSE_FILE} up -d --force-recreate

down:
	docker compose ${COMPOSE_FILE} down --remove-orphans

enter:
	@docker exec -it ${CONTAINER_PHP} bash

init:
	docker exec -it $(CONTAINER_PHP) composer install
	docker exec -it $(CONTAINER_PHP) composer run app-init

tests-all:
	docker exec -it ${CONTAINER_PHP_TEST} composer run tests

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