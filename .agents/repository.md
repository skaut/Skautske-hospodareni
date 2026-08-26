# Repository Working Instructions

These instructions apply to the entire repository. When making changes, respect the existing architecture and technologies. Do not introduce an alternative framework, database, build system, or test tool unless the user explicitly asks for it.

## Project Overview

Skautske hospodareni is a web application for accounting and financial administration of Czech Scout units. Authentication and organizational data come from SkautIS. Human documentation in `.docs` and UI texts are Czech, plain-language, and free of unnecessary jargon; retain technical identifiers and commands only when needed to perform a task. Agent instructions belong in `.agents` and are concise English. Keep technical identifiers, commit messages, and branch names in English according to the established practice.

## Target Environment

- The project targets PHP 8.3 exclusively. Do not maintain PHP 8.2 compatibility and do not use features that require PHP 8.4 or newer.
- The PHP image is based on `thecodingmachine/php:8.3-v4-fpm-node22`.
- The database is MySQL 8.0 with `utf8mb4` and `utf8mb4_czech_ci` collation.
- The web stack consists of Nginx and Traefik.
- The application time zone is `Europe/Prague`.

## Docker Is Mandatory

Run all project runtime, build, dependency, database, and check commands in Docker. Do not run `php`, `composer`, `bin/console`, `vendor/bin/*`, `node`, `npm`, `yarn`, `webpack`, or database clients against the project DB on the host.

Only repository reading and editing tools may be used on the host, for example `git`, `rg`, `sed`, and `apply_patch`.

Use this Compose file:

```bash
docker compose -f docker/docker-compose.yml ...
```

Preferred containers:

- `php` for the application, Composer, console, and frontend build.
- `php-test` for tests and quality checks.
- `mysql-test` for the test database.
- `selenium` for acceptance tests.

Make targets are allowed because project commands delegate to Docker. Prefer them when they cover the required operation.

- `make help` lists available commands.
- `make init` performs one-time development environment initialization: builds images, starts the stack, installs dependencies, runs migrations/application initialization, and builds the frontend.
- `make up` / `make down` starts or stops the development stack. The application runs at `http://moje-hospodareni.cz`, Adminer at `http://adminer.localhost`; locally, an entry `127.0.0.1 moje-hospodareni.cz` may be needed in `/etc/hosts`.
- `make test-init` initializes the test application and test database before the first test run.
- `make enter` opens a shell in the development PHP container, `make test-enter` in the test PHP container.

Run Docker commands, starting and stopping project services, and tests autonomously. Do not ask the user for permission to run tests. Resetting the test database is part of the normal test workflow. Do not delete the development database `mysql` volume or run destructive operations on development data unless explicitly requested.

Run project tools inside containers as the default `docker` user. Do not use `--user root` for Composer, `bin/console`, tests, PHPStan, PHP-CS-Fixer, or frontend builds because it would create root-owned cache and lock files. Root may only be used for one-time directory creation or permission repair; always rerun the subsequent project command as the `docker` user.

## Backend

- PHP 8.3 with `declare(strict_types=1)`.
- Nette 3.1 for the application, DI, forms, HTTP, security, and presenters.
- Latte 3 for server-side templates.
- Configuration and DI are in NEON files under `app/config`.
- Doctrine ORM and DBAL through Nettrine, Doctrine migrations in `migrations/<year>`.
- Always implement persisted schema changes through a migration. Do not edit the database schema manually as a substitute for a migration. If you generate a migration from mapping changes, use `bin/console migrations:diff` inside the Docker container.
- Autoloading is classmap-based through `app/`, not PSR-4. When renaming classes or moving files, consider the impact on the Composer classmap.
- Ublaboo DataGrid 6.10 for table views.
- Contributte/Fmasa Messenger for command, query, and event buses.
- SkautIS is an external integration system; isolate communication in infrastructure services and map it to DTOs.
- For dates without time, use the established `Cake\Chronos\ChronosDate`; for points in time, use `DateTimeImmutable`.
- Use existing domain value objects and custom Doctrine types instead of bypassing the domain model with primitive values.

### Service Emails

- Do not write service email texts directly in PHP services as concatenated strings or `implode()` blocks. Store them as plaintext Latte templates in `app/Model/emails`.
- Name service email templates with a recipient prefix: `user...latte` for emails to users and `admin...latte` for emails to administrators.
- Register new service emails through the shared system email mechanism, for example `SystemEmailTemplate`, and send them through the shared service mail layer, for example `SystemMailer`.
- A domain notification service should prepare recipients and template parameters; it should not handle text rendering, production/debug mailer selection, or construction of the system sender.
- Keep service emails as `text/plain` unless an HTML layout is explicitly needed.

## Architecture

The project uses a domain-organized write model and a separate read model.

- Write operations go through command buses, command handlers, aggregates, and domain services.
- Domain logic belongs in aggregates or domain services, not in presenters, Latte templates, or repository implementations.
- The domain defines interfaces for external dependencies; infrastructure implements them.
- Aggregates are loaded and saved through repository interfaces of the relevant bounded context.
- The read model is side-effect free and uses Query, Query Handler, and DTO.
- Presenters prepare the UI and delegate application and domain logic.
- Place new code in the appropriate bounded context under `app/Model`; UI belongs in `app/Presentation` or shared components in `app/Components`.
- `app/Component/Forms` is a historical area for existing form helpers and controls. Create new shared UI components in `app/Components` unless you are explicitly modifying an existing helper in `app/Component/Forms`.
- Respect the existing routing in `app/router/RouterFactory.php` and canonical URLs.
- The application uses classmap autoloading, so namespaces and physical paths are not enforced by a PSR-4 map; nevertheless, preserve the local directory convention of the bounded context.

When modifying older code, preserve local conventions unless they directly conflict with these rules. Do not perform unrelated refactoring.

### Write Model

- Domain logic belongs in DDD aggregates and domain services in the relevant bounded context.
- Send state changes as commands through `App\Model\Common\Services\CommandBus`; handlers belong in the corresponding `Handlers` directories.
- Aggregates may publish domain events that are handled by subscribers in the given context.
- Keep repository interfaces in the domain of the given context; Doctrine implementations belong in `app/Model/Infrastructure/Repositories/<context>`.

### Read Model

- The UI reads data through the query bus: a Query value object from `ReadModel/Queries` is processed by the corresponding handler in `ReadModel/QueryHandlers`.
- Follow the `FooQuery` -> `FooQueryHandler` convention; the custom PHPStan extension `QueryBusDynamicReturnTypeProvider` derives the return type of `QueryBus::handle()` from it.
- Query handlers return DTOs or simple read models and have no side effects.

### UI Layer

- Nette presenters and Latte templates are grouped by modules in `app/Presentation/<Module>/<Screen>`, shared UI components are in `app/Components`.
- Local configuration overrides belong in `app/config/config.*.local.neon`, never in versioned environment configurations.

## Frontend

- TypeScript 5.6 in strict mode.
- Webpack 5 as the only frontend build system.
- Sass/SCSS and PostCSS/Autoprefixer for styles.
- Tabler Core 1.4 and Bootstrap 5.3.8 for UI.
- Naja 1.7 for AJAX and redrawing Nette snippets.
- NProgress, Pikaday, and Moment with Czech locale are existing support libraries.
- Build visual elements on existing components, tokens, and utilities. Do not introduce React, Vue, Tailwind, another CSS framework, or a parallel icon system.
- Write new behavior as a TypeScript module in `frontend`; do not embed extensive JavaScript directly into Latte templates.
- After changing TypeScript or SCSS, run typecheck and frontend build in Docker. For frontend work, use the existing Yarn/Webpack commands, for example `yarn build`, `yarn build --watch`, and `yarn check-types`, always inside the project container.

## Testing

Tests use Codeception 4:

- Unit tests are in `tests/unit` and mainly cover aggregates, value objects, domain and application services, form helpers, isolated mail logic, and small utilities without a database.
- Integration tests are in `tests/integration` and verify repositories, database collaboration, handlers, use cases, and integration services. Keep test configuration or stubs near the specific integration area when that matches the existing structure.
- Acceptance tests are in `tests/acceptance` as Codeception `Cest` scenarios through Selenium/Chrome and cover stable user workflows, presenters, routing, and pages.
- Handle test doubles according to the nearest existing test; the project mainly uses Mockery, local fake objects, and stubs in integration tests.

Verify every change with the minimal relevant test set. Add an appropriate test for regression fixes and new behavior. Run tests without asking for permission.

### Evaluating CI Test Failures

If the user sends a failed test result and does not explicitly say otherwise, treat it as output from GitHub Actions. First check whether it is a CI false positive. Run the same or nearest relevant test locally in Docker, ideally with the same make target, group, or specific Codeception scenario that failed on the server.

If the local run does not reproduce the same error, treat the failure as a CI false positive and harden the test procedure against repeating it on the server. The goal is to have reliable tests that protect against bugs, verify functionality and process correctness, and do not force developers to repeat test runs on GitHub. If the available output does not objectively show why CI failed, you may request additional output or server artifacts before changing the test.

When you find an objective cause of flakiness or a false failure, check whether the same pattern appears in other tests. The fix must not break tests, reduce the test's ability to detect bugs, or change what is being tested. Stabilize only the waiting strategy, data isolation, environment preparation, or shared test helpers.

Unify test procedures:

- General behavior belongs in the main test class or a shared helper used by other tests.
- If a safer helper already exists for an operation, use it instead of the original unguarded variant, and when editing the affected test, replace other local uses of the same risky pattern too.
- Limit duplicate helpers and functions for the same thing; prefer one shared, robust procedure.
- Always take test timeouts from constants defined for tests, for example `AcceptanceTester::ELEMENT_LOAD_TIMEOUT`, instead of writing numbers by hand.
- Keep acceptance retry configuration in the shared acceptance ancestor, for example `BaseAcceptanceCest`. Do not define per-scenario retry counts, sleep durations, or local retry loops in individual `Cest` classes when the behavior is generally reusable.
- When acceptance navigation or a server-side form/action submit can render a transient SkautIS WSDL error page, use the shared SkautIS-aware helper from the acceptance ancestor. The low-level browser detection may live in `AcceptanceTester`, but individual tests should call the shared ancestor method so retry count and delay stay transparent in one place.
- Common acceptance wait helpers in `AcceptanceTester` should reclassify retryable SkautIS WSDL error pages into explicit SkautIS/WSDL assertion failures. Do not leave these cases as generic timeouts waiting for unrelated text or elements.
- A SkautIS WSDL communication failure that remains after all configured retries must fail with an explicit assertion message naming SkautIS/WSDL and the last URL. Do not let this case surface as a generic `NoSuchElementException` or timeout waiting for the expected page element.
- Tests must be runnable both individually and in sequence. If a test changes data, it must prepare or reset it before running so it does not depend on order and does not leave state that breaks later tests.
- If a test verifies a process requiring follow-up data, for example a travel order and a vehicle, it should be one uninterrupted test run or use an explicitly prepared isolated fixture.

Preferred commands:

```bash
make test-unit TEST=tests/unit/...
make test-integration TEST=tests/integration/...
make test-acceptance TEST=tests/acceptance/SomeCest.php:scenarioName
make check-phpstan
make check-cs-check
make check-cs
make check-latte
make test-mapping
make fix
```

For a complete check, use:

```bash
make ci
```

If a direct command is needed, run it in `php-test`, for example:

```bash
docker compose -f docker/docker-compose.yml exec -T php-test \
    vendor/bin/codecept run unit tests/unit/...
```

### Test Conventions

- Place a new test according to the real boundary of the change: pure domain logic in `tests/unit`, database or configuration collaboration in `tests/integration`, user walkthroughs in `tests/acceptance`.
- When extending an existing area, copy the style of the nearest test in the given bounded context, including naming, fixture data, and dependency construction.
- Verify repositories and schema through integration tests against the test database, not by mocking the repository implementation.
- Replace SkautIS and other external systems in tests through their interfaces with a fake object or stub; network communication must not be part of normal tests.
- Add acceptance tests for stable and user-significant workflows. For a small presenter branch, prefer a unit or integration test of the service that owns the decision.

## Quality And Style

- PHPStan runs at level 6 with configuration in `phpstan.neon`.
- PHP-CS-Fixer uses rules from `.php-cs-fixer.dist.php`.
- `make check-cs` fixes the coding standard, `make check-cs-check` is the CI dry run.
- `make fix` runs fixable checks without tests.
- Verify Latte through `vendor/bin/latte-lint`.
- Verify TypeScript through `yarn check-types`.
- Preserve LF line endings.
- Use precise types, return types, and concrete array shapes where they improve static analysis.
- Do not use native PHP `assert()` in application code. For runtime invariants and type narrowing, use an explicit guard that throws an exception.
- Do not fix errors by suppressing checks when the cause can be removed.
- Do not silence PHP deprecations, static-analysis findings, test warnings, or build errors as a substitute for a real fix. Prefer updating the codebase and, when the warning comes from third-party tooling, evaluate upgrading the affected package stack. If the upgrade is broad or risky, report the scope and blockers before making the dependency change.
- Do not edit generated files, build artifacts, or contents of `vendor` and `node_modules`.

## Implementation Consistency And Best Practices

- Unify how things are implemented. When more than one pattern exists for the same job (form value access, DI service wiring, session handling, HTTP responses, validators, routing), pick the one that matches current best practice and converge on it instead of adding a third variant.
- Follow current best practices for the stack in use: modern, non-deprecated Nette APIs compatible with the installed version and its approved upgrade path, Latte 3, RESTful conventions for HTTP endpoints, and idiomatic PHP 8.3+ (typed properties, enums, constructor promotion, first-class callables, `readonly` where it fits). Prefer non-deprecated framework APIs (for example `RouteList::addRoute()`/`add()` over `$router[]`, `SessionSection::get()/set()` over magic properties, `getConfigSchema()` over legacy `$defaults`).
- Keep it simple (KISS). Prefer the smallest, most direct solution that reads like the surrounding code. Do not add abstraction, indirection, configuration, or a wrapper package for a problem the framework already solves. Inline a trivial dependency rather than keeping a self-serving one.
- Do not mix idioms within a change. If you touch a file that uses an outdated idiom relevant to your change, migrate that usage to the unified approach rather than matching the old style.

## Dependencies And Stack Changes

- PHP dependencies are managed by Composer, frontend dependencies by Yarn.
- Respect `composer.lock` and `yarn.lock`; after an intentional dependency change, update the relevant lock file in Docker.
- Before adding a new dependency, check whether the problem is already solved by a framework or library in use.
- When existing dependencies emit compatibility warnings or deprecations on the target PHP version, prefer moving the application and dependency stack forward over adding suppressions or lowering error visibility.
- Add a new technology or replace any of the technologies listed above only when explicitly requested by the user.

## Pull Requests And Commits

- Changes to `master` go through a pull request, green CI, and maintainer approval.
- Larger changes should have an existing issue or a clear prior agreement.
- Prefer small, topic-focused commits.
- Write commit messages in English with a conventional-commit prefix, for example `fix:`, `refactor:`, `test:`, or `docs:`.

## UI And UX Review

- Follow `.agents/ui-ux-guideline.md` for every new or changed user-facing route.
- After relevant tests, PhpStorm inspections, static analysis and frontend checks, review the complete changed route rather than only edited elements: desktop and 375 px mobile, light and dark theme, keyboard focus, navigation, flashes, forms, help and grids.
- Modernize the complete touched template and its local styling when legacy markup prevents it from following the guideline. Do not introduce a new layout for a local variation.
