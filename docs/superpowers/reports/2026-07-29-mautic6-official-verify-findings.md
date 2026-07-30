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
  Fixed one `$this->forward(...)` call site in the same file, fed by three string-literal assignments (`$detailView`, and two `$formView` variants) all using the legacy single-colon `Class:method` controller-reference syntax. Symfony's `ControllerResolver::createController()` in this Symfony version only accepts the double-colon `Class::method` syntax; the single-colon form throws a loud `InvalidArgumentException` (`Controller "...ClassName:methodName" does neither exist as service nor as class.`) rather than failing silently — this was a second, previously-masked failure that surfaced only once the first bug (`d2105e6f`) was fixed, since the array-value crash happened earlier in the same method.

## Environment-only notes (not plugin bugs, no code changes made)

These were observed during setup/testing and are recorded here for completeness, since they affect anyone else re-running this verification on the same host or a similarly fresh instance:

- **mkcert / HTTPS gap (host tooling):** `mkcert` is not installed on this host, so DDEV's router never generates a valid TLS certificate for the throwaway `mautic-6-official` instance (empty/corrupt PEM). Worked around by switching the test instance's `site_url` to plain HTTP (`http://mautic-6-official.ddev.site:8080`); no system-level changes were made. This will block anyone testing HTTPS-dependent behavior on this host until `mkcert` is installed — unrelated to the plugin.
- **Doctrine migrations metadata-storage table (core Mautic install-flow quirk):** on this fresh Mautic 6.0.9 install, core Mautic's own `doctrine:migrations:version` metadata-storage table was never initialized (confirmed via `InstallService::finalMigrationStep()`). This didn't block anything tested in this plan, but any future `doctrine:migrations:migrate` call on this instance would need `doctrine:migrations:sync-metadata-storage` run first. Not a CustomObjectsBundle concern.
- **redis-commander DDEV addon (host/infra):** `ddev start` initially failed because the optional `redis-commander` DDEV addon couldn't pull its image (GHCR registry pull denial). Worked around by disabling the addon (renamed to `.disabled`) in the throwaway `mautic-6-official` worktree — reversible, unrelated to the plugin.
- **Sibling single-colon controller-string bug — now fixed.** The single-colon `Class:method` pattern fixed in commit `08ef6cc7` (see above) also existed in `Controller/CustomObject/DeleteController.php:30` (feeding both `forward()` calls in that file, at lines 57 and 67) and `Controller/CustomObject/CancelController.php:32`. Neither was on the flow tested in the original pass (create Custom Object / create Custom Item), so neither was fixed at the time — it was flagged here as a known-issue class instead. A follow-up pass (commit `d4c895f3` on `chore/mautic6-official-verify`) applied the same double-colon fix to both files; a repo-wide grep confirmed no other single-colon controller-reference strings remain in `Controller/`.

  Worth recording for the historical record: `CancelController.php`'s failure mode was not identical to `SaveController.php`'s. `CancelController` extends Mautic core's `CommonController::postActionRedirect()`, which only calls `forward()` on XHR requests (Mautic's normal cancel path uses XHR). With the single-colon bug in place, this surfaced as an uncaught `InvalidArgumentException` resulting in HTTP 500 — not the HTTP 400 seen for the `SaveController` bug, which was actually a different, unrelated `InputBag` bug (`d2105e6f`), not this one. That distinction is now moot in practice since both files are fixed, but it's useful context for understanding what class of Mautic 6 compatibility issue this was.

## Known gap: unit test suite does not run under Symfony 6.4

This plugin's own `Tests/Unit` suite does not run to completion under Symfony 6.4 on Mautic 6. This is a real, separate finding — invisible to any single task's diff review — recorded here so the next person doesn't have to rediscover it:

- `Tests/Unit/EventListener/CustomItemPostSaveSubscriberTest.php:40` — fatal error: `Declaration of RequestStack@anonymous::getCurrentRequest() must be compatible with RequestStack::getCurrentRequest(): ?Request`. A test double violates a Symfony 6 native return-type declaration, which aborts the broader unit suite partway through.
- `Tests/Unit/Controller` subsuite: 89 tests, 9 errors — all the same root cause: Symfony 6 added native return types (e.g. `FormInterface::get(): self`, `HttpKernelInterface::handle(): Response`) that older test doubles in this suite don't declare or match.
- Three of those 9 errors are in `Tests/Unit/Controller/CustomItem/SaveControllerTest.php` — the test file for the very controller this branch fixed. Notably, `SaveControllerTest.php:179-186` already POSTs an array-shaped `custom_item` payload through `saveAction()` — it would have caught the `InputBag::get()` bug (fixed in `d2105e6f`) via `phpunit` alone, with no browser needed, if the suite could run.

Not fixed in this pass — documentation only. See Recommendation below.

## Recommendation

Merge `chore/mautic6-official-verify` into `6.x`. All fixes on the branch (`d2105e6f`, `08ef6cc7`, and the follow-up `d4c895f3`/`87a4d8a6` covering the sibling `DeleteController`/`CancelController` instances) are small, targeted, and independently re-verified (review-clean, cache:clear runs clean, full create-flow re-tested end-to-end with zero errors in logs). A repo-wide grep confirms no single-colon `Class:method` controller-reference strings remain anywhere in `Controller/`.

The natural next work item on `6.x` is fixing the unit-test-double return-type mismatches described in "Known gap" above. That gap meant this branch's own regression (`d2105e6f`) could have been caught by `phpunit` alone via the existing `SaveControllerTest.php` coverage, with no browser round-trip needed — restoring the suite would close that gap for future changes.

No action needed regarding the mkcert/HTTPS gap or the migrations-metadata-storage note — both are environment-specific and out of scope for the plugin, but worth keeping in mind for future test runs on this host.
