# AGENTS.md

This file provides guidance to generative AI agents when working with code in this repository.

## Project Overview

Skautské hospodaření — a web application for accounting/economy management of Czech Scout units. PHP 8.3, Nette framework, Doctrine ORM, MySQL, with a TypeScript/Sass frontend built by webpack. Authentication and organizational data come from SkautIS (the Czech Scouting information system). Documentation in `.docs/`, UI texts, and issue discussions are in Czech.

## Development Environment

Everything runs in Docker (`docker/docker-compose.yml`); all common commands are wrapped in the `Makefile` (`make help` lists them). PHP tooling (composer, codecept, `bin/console`) must run inside the containers — via make targets, or a shell with `make enter` (dev container) / `make test-enter` (test container). Tests use a separate container (`hskauting.app-test`) and a separate `mysql-test` database.

- `make init` — one-time setup: builds images, starts the stack, installs deps, runs migrations, builds frontend
- `make up` / `make down` — start/stop the dev stack; the app runs at http://moje-hospodareni.cz (requires `127.0.0.1 moje-hospodareni.cz` in `/etc/hosts`), Adminer at http://adminer.localhost
- `make test-init` — initialize the test app + test DB (needed before the first test run)

### Tests (Codeception)

- `make test-unit`, `make test-integration` (needs DB), `make test-acceptance` (Selenium browser tests)
- Single test: `make test-unit TEST=App/RouterFactoryTest`, `make test-integration TEST=tests/integration/SomeCest.php`, `make test-acceptance TEST=tests/acceptance/SomeCest.php:scenarioName`
- `make ci` — the full pipeline as run in GitHub Actions

### Code Quality

- `make check-phpstan` — PHPStan level 6; custom project rules live in `code-quality/`
- `make check-cs` — apply coding standard (php-cs-fixer, derived from Doctrine Coding Standard); `make check-cs-check` is the CI dry run
- `make check-latte` — lint Latte templates
- `make fix` — runs check-cs + check-latte + check-phpstan
- `make test-mapping` — validate Doctrine mapping against the migrations-produced schema

### Database & Frontend

- Migrations live in `migrations/`; inside the container: `bin/console migrations:migrate`, generate a new one from mapping changes with `bin/console migrations:diff`
- Frontend (yarn is available in the dev container): `yarn build`, `yarn build --watch`, type-check with `yarn check-types`

## Architecture

The domain is split into bounded contexts under `app/Model/` (Cashbook, Payment, Travel, Event, Unit, Bank, Mail, Skautis, ...) with a CQRS-style separation of write and read models. Autoloading is classmap-based over `app/` (not PSR-4).

### Write model

- Domain logic lives in DDD aggregates (extending `App\Model\Common\Aggregate`) and domain services inside each bounded context.
- State changes are dispatched as Commands (`<context>/Commands/`) through `App\Model\Common\Services\CommandBus` and processed by handlers in `<context>/Handlers/`. Buses are built on Symfony Messenger (fmasa/messenger).
- Aggregates raise domain events (`<context>/Events/`) consumed by subscribers (`<context>/Subscribers/`).
- Repository **interfaces** are declared in `<context>/Repositories/`; their Doctrine implementations live in `app/Model/Infrastructure/Repositories/<context>/`. Anything touching the outside world (database, SkautIS, Google APIs, banks) belongs to Infrastructure — the domain only defines interfaces for it.

### Read model

- The UI reads data exclusively through the query bus: a Query value object from `<context>/ReadModel/Queries/` is passed to `QueryBus::handle()` and processed by a matching handler in `<context>/ReadModel/QueryHandlers/` (`FooQuery` → `FooQueryHandler`) returning DTOs. Read model has no side effects.
- A custom PHPStan extension (`QueryBusDynamicReturnTypeProvider`) infers the return type of `QueryBus::handle()` from the handler, so the query/handler naming convention must be kept.

### UI layer

- Nette presenters + Latte templates are grouped per module in `app/Presentation/<Module>/<Screen>/` (presenter and its `.latte` templates side by side). Base presenters are in `app/presenters`, shared components in `app/Component`/`app/Components`.
- DI configuration is in neon files under `app/config/`; `config.local.neon` holds local overrides.
- SkautIS communication is wrapped in dedicated services mapping I/O to DTOs, defined by interfaces so tests can substitute fakes.

## Testing Conventions

From `.docs/tipy-pro-testovani.md`:

- Aggregates and domain services: unit tests covering all scenarios/code paths
- Repositories: integration tests against the real (test) database
- Listeners/subscribers: covered by the integration test of the main action, not separately
- SkautIS communication: replace the service interface with a fake object
- Presenters: acceptance tests, only where simple

## Pull Requests

All changes to master go through PRs and require a green CI build and maintainer approval; larger changes need an existing issue. Prefer small commits; amend existing commits via git fixups. Commit messages use conventional-commit-style prefixes (`fix:`, `refactor:`, ...) in English.
