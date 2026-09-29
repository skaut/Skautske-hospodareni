# Upgrade plan: Nette 4

**Status:** not implemented on the current branch (verified 2026-08-26). This agent-facing document covers the Nette 4 upgrade only.

## Goal and scope

In this project, Nette 4 means moving `nette/utils` to 4.x with compatible `nette/mail` and `nette/robot-loader` versions. Other Nette packages remain on compatible 3.x lines.

Keep the upgrade isolated. Do not combine it with unrelated dependency upgrades, database changes, UI work, or functional refactors.

## Verified baseline and blockers

The project currently uses `nette/utils 3.2.10`. `composer why-not nette/utils ^4` in Docker identified these blockers:

| Dependency | Current state | Required action |
| --- | --- | --- |
| `nette/robot-loader` | 3.4 requires utils 3 | Upgrade to a utils-4-compatible line. |
| `contributte/menu-control` | 2.2.1 requires utils 3 | Upgrade to 3.x. |
| `kdyby/forms-replicator` | 2.0.0 requires utils 3 | Replace with `contributte/forms-multiplier` and migrate usage. |
| `skautis/nette` | Latest stable 2.2.0 supports only utils 3 | Remove the package and move its required DI integration into the application. |
| `contributte/codeception` | 1.3.2 requires utils 3 | Upgrade together with Codeception 5. |
| `nette/finder` | Transitive conflict with utils 4 | Remove through the dependency graph; the application does not use it directly. |

Re-check resolution with Composer in Docker before editing dependencies. This document does not prescribe patch versions: use the newest mutually compatible stable versions Composer resolves in `composer.json` and the lock file.

## Implementation steps

1. Run `composer update --dry-run` for the target set. Upgrade Nette dependencies, `contributte/menu-control`, and the Codeception 4 → 5 stack together.
2. Remove `skautis/nette`; implement only its required integration in `App\Model\Skautis`:
   - DI extension, cache and session adapters, and Tracy panel;
   - preserve `skautis.config`, `skautis.webServiceFactory`, `skautis.wsdlManager`, `skautis.session`, `skautis.user`, `skautis.skautis`, and `skautis.panel` service names;
   - switch the configured extension and rebuild classmap autoloading after adding classes;
   - use `getConfigSchema()`, `SessionSection::get()/set()`, and `Nette\Caching\Storage`.
3. Replace `kdyby/forms-replicator` with `contributte/forms-multiplier` in `ChitForm`, `SplitPaymentDialog`, `InvoiceForm`, and `CustomControlFactories`. Preserve dynamic-form-item behavior and cover it in the nearest existing form tests.
4. Fix only compatibility failures found by static analysis and tests. Add tests at the correct boundary: DI wiring integration tests, adapter unit tests, and nearest domain or infrastructure tests.
5. Update Codeception 5 configuration, modules, and generated support classes. Change acceptance scenarios only for demonstrated upgrade incompatibilities.

## Required verification

Run all commands in Docker in this order: Composer and autoload → `make check-phpstan` → `make check-cs-check` → `make check-latte` → unit and integration tests → targeted acceptance tests → `make ci`.

Success requires utils 4 in the lock file, no remaining blocker from the table, the same resolvable SkautIS service names, and passing checks.

## Document maintenance

After each isolated run, update only the verified baseline, remaining blockers, and next concrete step. Do not add change history, test-result logs, or unrelated dependency roadmaps.
