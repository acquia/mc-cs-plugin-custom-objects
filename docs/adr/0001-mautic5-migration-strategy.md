# ADR-0001: Mautic 5 Migration Strategy for CustomObjectsBundle

**Date:** 2026-03-05
**Status:** Accepted
**Deciders:** Engineering

---

## Context

The `CustomObjectsBundle` plugin was originally developed for Mautic 4. A partial Mautic 5 migration had been committed to the `staging` branch (91 commits ahead of origin), covering PHP-to-Twig template conversions and some controller updates. However, the branch was not runnable: `cache:clear` produced a fatal error that prevented the application from booting.

The goal was to make the plugin functional on Mautic 5.2.9 (Symfony 5.4, PHP 8.3) inside a DDEV Docker environment, with minimal risk of introducing new regressions.

---

## Decision 1: Iterative crash-first debugging over a full audit

**Chosen approach:** Fix only the errors that prevent the application from booting, one at a time. Do not attempt a comprehensive code review or proactive refactoring pass.

**Alternatives considered:**

- **Full audit first:** Read every file, produce a complete list of incompatibilities, fix all at once.
  *Rejected:* The plugin is large (~150 files). A full audit before any running instance exists produces unverifiable fixes. It also risks fixing problems that don't actually block boot, wasting effort.

- **Rewrite from scratch targeting Mautic 5 idioms.**
  *Rejected:* The staging branch already had significant migration work. Discarding it would lose domain knowledge encoded in those 91 commits.

**Tradeoffs:**

| Pro | Con |
|-----|-----|
| Each fix is immediately verified by a real boot cycle | Deferred issues (e.g. PHP 8.1 nullable deprecation warnings in ~6 files) remain in the codebase |
| Minimal blast radius per change | Requires iterating multiple times if there are many fatal errors |
| Working instance reached quickly | Does not guarantee correctness beyond "it boots and renders" |

---

## Decision 2: Fix `DynamicContentSubscriber` by removing traits, not re-implementing them

**Background:**

The `staging` branch left `DynamicContentSubscriber` in a broken intermediate state. The original Mautic 4 implementation used two traits:

- `MatchFilterForLeadTrait` — evaluated segment filters against a contact
- `DbalQueryTrait` — provided DBAL query builder helpers

The migration had:
1. Removed the trait method bodies from the class
2. Left the `use MatchFilterForLeadTrait; use DbalQueryTrait;` declarations without namespace imports
3. Added `ContactFilterMatcher $contactFilterMatcher` to the constructor but never used it in `hasCustomObjectFilters()`

This caused a PHP fatal error (`Class "MatchFilterForLeadTrait" not found`) on every request.

**Chosen approach:** Remove both trait declarations entirely. Wire `ContactFilterMatcher` (already injected) as the sole delegate for filter matching. Clean up the dead code in `hasCustomObjectFilters()`.

**Alternatives considered:**

- **Re-add namespace imports for the traits and keep using them.**
  *Rejected:* The traits were removed from Mautic 5 core (`MatchFilterForLeadTrait` no longer exists in `mautic/core-lib ^5.0`). Importing them is impossible.

- **Copy the trait logic inline into the class.**
  *Rejected:* `ContactFilterMatcher` already provides the equivalent abstraction and is already instantiated. Duplicating trait logic would create a maintenance burden and diverge from the Mautic 5 pattern.

- **Rewrite `hasCustomObjectFilters()` to use `ContactFilterMatcher::match()` directly.**
  *Rejected:* `hasCustomObjectFilters()` only needs to detect *whether* a filter is a custom-object filter, not evaluate it. Using `QueryFilterFactory::configureQueryBuilderFromSegmentFilter()` as a type-check (throws `InvalidSegmentFilterException` for non-CO filters) is the correct existing pattern and preserves the separation of concerns.

**Resulting design:**

```
evaluateFilters()
  └─ hasCustomObjectFilters()          ← type detection via QueryFilterFactory
       uses: QueryFilterFactory::configureQueryBuilderFromSegmentFilter()
       throws: InvalidSegmentFilterException for non-CO filters

  └─ ContactFilterMatcher::match()     ← actual evaluation
       called only when CO filters are confirmed present
```

**Tradeoffs:**

| Pro | Con |
|-----|-----|
| Consistent with how `CampaignSubscriber` uses `ContactFilterMatcher` | `hasCustomObjectFilters()` abuses `configureQueryBuilderFromSegmentFilter()` as a type predicate, which is implicit |
| No duplication of matching logic | The exception-as-control-flow pattern in `hasCustomObjectFilters()` is non-obvious to new readers |
| Both collaborators already existed and were tested | |

---

## Decision 3: Copy plugin directory instead of symlinking into DDEV

**Background:**

The plugin source lives at `/home/edouard/WS/webanyone/mc-cs-plugin-custom-objects`. Mautic loads plugins from `plugins/*/`. The DDEV web container mounts only the Mautic project root at `/var/www/html`; paths outside that mount do not exist inside the container.

**Chosen approach:** `cp -r` the plugin directory to `plugins/CustomObjectsBundle/` inside the Mautic project. Changes to the plugin source must be manually synced.

**Alternatives considered:**

- **Absolute symlink** (`ln -s /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects plugins/CustomObjectsBundle`).
  *Rejected:* The absolute host path does not exist inside the Docker container. `file_exists()` and `realpath()` calls in `BundleMetadataBuilder` / `EntityMetadata` return false, so Doctrine never discovers the entities.

- **Relative symlink** (`ln -s ../../mc-cs-plugin-custom-objects plugins/CustomObjectsBundle`).
  *Rejected:* Resolves inside the container to `/var/www/mc-cs-plugin-custom-objects`, which is also outside the mount. Same failure mode as absolute symlink.

- **Mount the plugin directory as an additional DDEV volume.**
  *Not pursued:* Would require modifying `.ddev/config.yaml` and restarting the stack. Valid long-term solution but out of scope for the immediate goal.

- **Install via Composer path repository.**
  *Not pursued:* Requires the plugin to have a valid `composer.json` with `type: mautic-plugin` and Mautic's plugin installer configured. Could be the right long-term approach.

**Root cause of symlink failure:** `BundleMetadataBuilder::getOrmConfig()` calls `EntityMetadata::build()`, which does:
```php
$entityDirectory = realpath($bundleDir . '/Entity');
if (!file_exists($entityDirectory)) { return; }
```
`realpath()` resolves symlinks to their target. When the target is outside the Docker mount, `realpath()` returns `false`, `file_exists(false)` returns `false`, and entity mapping is silently skipped.

**Tradeoffs:**

| Pro | Con |
|-----|-----|
| Simplest solution, works immediately | Two copies of the code to keep in sync during development |
| No DDEV configuration changes required | Easy to forget to sync after editing plugin source |
| Full Docker visibility of all files | Not suitable as a long-term dev workflow |

**Recommended long-term resolution:** Add `mc-cs-plugin-custom-objects` as an additional DDEV mount:
```yaml
# .ddev/config.yaml
web_mounts:
  - source: ../mc-cs-plugin-custom-objects
    target: /var/www/html/plugins/CustomObjectsBundle
```
This eliminates the copy entirely.

---

## Deferred issues (out of scope)

The following were identified but not fixed, consistent with Decision 1:

- PHP 8.1 nullable parameter deprecation warnings in ~6 files (`CustomItemRouteProvider`, `SessionProviderFactory`, `CustomObjectRepository`, `CustomCommonRepository`, `CustomItemImportModel`, `CustomObjectsBundle`). These are warnings, not fatal errors, and do not affect runtime behaviour on PHP 8.3.
- The existing unit test for `DynamicContentSubscriber` was broken (wrong constructor argument order, reference to undeclared `$this->loggerMock`, tests for deleted code). **This was fixed as part of this migration** — see the rewritten `DynamicContentSubscriberTest`.
