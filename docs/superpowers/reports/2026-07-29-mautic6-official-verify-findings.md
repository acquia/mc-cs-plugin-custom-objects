# Mautic 6 Official Instance Verification — Findings

**Date:** 2026-07-29
**Plugin branch tested:** 6.x (via chore/mautic6-official-verify)
**Mautic version:** 6.0.9 (official upstream, unmodified)

## Result

PASS WITH FIXES — after two small fixes to `Controller/CustomItem/SaveController.php`, the full create-Custom-Object / create-Custom-Item flow works end-to-end against a fresh, official Mautic 6.0.9 instance.

## Success criteria

| Criterion | Result | Notes |
|---|---|---|
| cache:clear / cache:warmup clean | ✅ | Clean run against fresh 6.0.9, no plugin changes needed. |
| mautic:plugins:reload installs schema | ✅ | CustomObjectsBundle registered; all DB tables confirmed present via direct DB inspection. |
| Admin UI loads | ✅ | Login and dashboard render correctly. |
| Custom Objects list renders | ✅ | List view and left-nav item render correctly after creating a Custom Object. |
| Create Custom Object + Custom Item works | ✅ | Custom Object "Product" (with a Description field) created cleanly on first attempt. Custom Item creation initially failed with HTTP 400 (see Fixes applied) and passes after the two fixes below — verified end-to-end: create → Save → edit view (200) → Save & Close → detail view (200), DB row persisted, zero `mautic.ERROR`/`mautic.CRITICAL` log entries during the window. |

## Fixes applied

Both commits are on branch `chore/mautic6-official-verify` (built off `6.x`) in the plugin repo:

- **`d2105e6f`** — `fix(CustomItem): use InputBag::all() for array-shaped custom_item field`
  Fixed `Controller/CustomItem/SaveController.php:37`: `$request->request->get('custom_item')` was called on an array-shaped form field. Symfony 6.4's `InputBag::get()` rejects non-scalar values and threw, producing an HTTP 400 ("Input value \"custom_item\" contains a non-scalar value") on every Custom Item save. Changed to `InputBag::all('custom_item')`, which is the correct accessor for array-shaped request parameters.

- **`08ef6cc7`** — `fix(CustomItem): use double-colon controller syntax in SaveController::forward()`
  Fixed three call sites in the same file where `$this->forward(...)` was passed a legacy single-colon `Class:method` controller-reference string. Symfony's `ControllerResolver::createController()` in this Symfony version only accepts the double-colon `Class::method` syntax; the single-colon form is silently unresolvable and caused a second, previously-masked failure once the first bug was fixed. This was uncovered only after fixing `d2105e6f`, since the array-value crash happened earlier in the same method.

## Environment-only notes (not plugin bugs, no code changes made)

These were observed during setup/testing and are recorded here for completeness, since they affect anyone else re-running this verification on the same host or a similarly fresh instance:

- **mkcert / HTTPS gap (host tooling):** `mkcert` is not installed on this host, so DDEV's router never generates a valid TLS certificate for the throwaway `mautic-6-official` instance (empty/corrupt PEM). Worked around by switching the test instance's `site_url` to plain HTTP (`http://mautic-6-official.ddev.site:8080`); no system-level changes were made. This will block anyone testing HTTPS-dependent behavior on this host until `mkcert` is installed — unrelated to the plugin.
- **Doctrine migrations metadata-storage table (core Mautic install-flow quirk):** on this fresh Mautic 6.0.9 install, core Mautic's own `doctrine:migrations:version` metadata-storage table was never initialized (confirmed via `InstallService::finalMigrationStep()`). This didn't block anything tested in this plan, but any future `doctrine:migrations:migrate` call on this instance would need `doctrine:migrations:sync-metadata-storage` run first. Not a CustomObjectsBundle concern.
- **redis-commander DDEV addon (host/infra):** `ddev start` initially failed because the optional `redis-commander` DDEV addon couldn't pull its image (GHCR registry pull denial). Worked around by disabling the addon (renamed to `.disabled`) in the throwaway `mautic-6-official` worktree — reversible, unrelated to the plugin.
- **Deferred: same legacy single-colon controller-string bug elsewhere.** The single-colon `Class:method` pattern fixed in commit `08ef6cc7` (see above) also exists in `Controller/CustomObject/DeleteController.php` and `Controller/CustomObject/CancelController.php`. Neither is on the flow tested in this plan (create Custom Object / create Custom Item), so neither was fixed here. Flagging as a known-issue class for a future pass — anyone exercising Custom Object delete/cancel flows under Symfony 6.4 should expect the same `forward()` resolution failure.

## Recommendation

Merge `chore/mautic6-official-verify` into `6.x`. Both fixes are small, targeted, and independently re-verified (review-clean, full flow re-tested end-to-end with zero errors in logs). Before merging, consider opening a small follow-up task to sweep `Controller/CustomObject/DeleteController.php` and `Controller/CustomObject/CancelController.php` for the same single-colon `forward()` syntax issue, since it's the same bug class and will surface the same way (HTTP 400 / unresolvable controller) whenever those flows are exercised under Symfony 6.4.

No action needed regarding the mkcert/HTTPS gap or the migrations-metadata-storage note — both are environment-specific and out of scope for the plugin, but worth keeping in mind for future test runs on this host.
