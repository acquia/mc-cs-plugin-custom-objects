# Mautic 6 Official Instance Verification — Findings

**Date:** 2026-07-29
**Plugin branch tested:** 6.x (via chore/mautic6-official-verify)
**Mautic version:** 6.0.9 (official upstream, unmodified)

## Result

PASS WITH FIXES — after fixes to `Controller/CustomItem/SaveController.php`, `EventListener/CampaignSubscriber.php`, and the `TokenSubscriber` DI wiring, the plugin's core CRUD, Segment filtering, and Campaign conditions all work end-to-end against a fresh, official Mautic 6.0.9 instance, and its unit test suite (618 tests) now passes in full under Symfony 6.4.

## Success criteria

| Criterion | Result | Notes |
|---|---|---|
| cache:clear / cache:warmup clean | ✅ | Clean run against fresh 6.0.9, no plugin changes needed. |
| mautic:plugins:reload installs schema | ✅ | CustomObjectsBundle registered; all DB tables confirmed present via direct DB inspection. |
| Admin UI loads | ✅ | Login and dashboard render correctly. |
| Custom Objects list renders | ✅ | List view and left-nav item render correctly after creating a Custom Object. |
| Create Custom Object + Custom Item works | ✅ | Custom Object "Product" (with a Description field) created cleanly on first attempt. Custom Item creation initially failed with HTTP 400 (see Fixes applied) and passes after the two fixes below — verified end-to-end: create → Save → edit view (200) → Save & Close → detail view (200), DB row persisted, zero `mautic.ERROR`/`mautic.CRITICAL` log entries during the window. |

## Headline features (beyond the original 5 criteria)

Tested after the initial pass, since these are the plugin's actual value proposition (per its own README) and hadn't been exercised yet:

| Feature | Result | Notes |
|---|---|---|
| Segment filtering by Custom Object field values | ✅ | Worked correctly on the first attempt, no fix needed. A segment filtered on "Products : Description contains Blue" correctly included a contact linked to a matching item and excluded one linked to a non-matching item, verified via `mautic:segments:update` + UI membership check. |
| Campaign condition on Custom Object field values | ✅ (after fix) | Initially broken: opening the "Product field value" condition's config form threw a 500 (`Twig\Error\RuntimeError: Neither the property "form" ... exist`). Fixed (see Fixes applied). After the fix: a campaign condition correctly routed a matching contact down the Yes branch and a non-matching contact down the No branch, verified via `mautic:campaigns:trigger` + tag checks on both contacts. |

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

- **`1f62fb32`** — `fix(campaign): use plugin's own form theme for field-value condition`
  `EventListener/CampaignSubscriber.php` registered the "Product field value" campaign condition with Mautic **core's** form-theme template (`@MauticForm/FormTheme/FieldValueCondition/_campaignevent_form_field_value_widget.html.twig`), which expects a `form.form` property that this plugin's `CampaignConditionFieldValueType` doesn't have (`field`/`operator`/`value` instead) — crashing with a Twig `RuntimeError` every time the condition's config form was opened. The plugin already shipped the correct template at `Resources/views/FormTheme/FieldValueCondition/campaign_condition_field_value_widget.html.twig`; it was just never referenced. Fixed by pointing at it. Verified end-to-end via a real campaign: matching contact → Yes branch, non-matching contact → No branch.

- **`048d4457`** — `fix(di): correct TokenSubscriber's argument list and missing import`
  A genuine **production** bug, not a test-only issue, found while making the unit test suite pass: `TokenSubscriber`'s constructor gained a `CustomFieldModel` parameter and switched its trailing parameter from `ContactFilterMatcher` to a scalar `leadCustomItemFetchLimit` at some point, but `Config/config.php`'s explicit service definition (`custom_object.emailtoken.subscriber`) was never updated to match — still passing the old 10-argument list. This would have thrown a fatal error the first time Mautic actually tried to replace a Custom-Object email token (`{customobject=...}`), since Symfony's explicit-argument services aren't type/count-checked at container compile time, only at instantiation. Also fixed a missing `use` import for `CustomFieldModel` in `TokenSubscriber.php` itself: without it, the constructor's type-hint silently resolved to the current namespace (`EventListener\CustomFieldModel`, which doesn't exist) instead of `Model\CustomFieldModel`, so even a corrected argument list would still have been rejected. Verified by instantiating the service through the real compiled container (`$container->get('custom_object.emailtoken.subscriber')`), not just by fixing the unit test.

## Unit test suite: now passes in full under Symfony 6.4

The original pass through this plugin found the `Tests/Unit` suite did not run to completion under Symfony 6.4 on Mautic 6 and left it as a documented gap. That gap has since been closed: **all 618 unit tests pass** (11 pre-existing, intentional skips unrelated to Symfony 6; 0 errors, 0 failures).

28 test-double failures were fixed across 8 files, all variants of the same underlying theme — Symfony 6 added or now strictly enforces native return-type declarations (`RequestStack::getCurrentRequest(): ?Request`, `FormInterface::get(): FormInterface`, `HttpKernelInterface::handle(): Response`, `ContainerBuilder::findDefinition(): Definition`, `FormBuilderInterface`/`FormConfigBuilderInterface`) that older hand-rolled or auto-generated test doubles didn't satisfy — plus one unrelated pre-existing test-environment gap (a missing export directory) and the `self::$container` → `static::getContainer()` KernelTestCase migration. Commits, all on `chore/mautic6-official-verify`:

- **`9fc64c5c`** — `RequestStack` test double missing the `?Request` return type on `getCurrentRequest()`, a fatal error that aborted the entire PHPUnit process before any later test file could run.
- **`8c115565`** — `FormControllerTest`'s `HttpKernelInterface::handle()` mocks returning `null` instead of a real `Response`.
- **`5800a4b0`** — `CustomFieldTypePassTest`'s `ContainerBuilder::findDefinition()` mock supplied only 1 of 3 expected consecutive return values, so PHPUnit filled the gap with `null`.
- **`f8910dcc`** — `CampaignConditionFieldValueTypeTest` mocked the narrower `FormConfigBuilderInterface` where the wider `FormBuilderInterface` (which extends it) was required.
- **`dc8dd84d`** — `SaveControllerTest` (both `CustomItem` and `CustomObject`) mocked `$form->get('buttons')->get('save')` as a bare `ClickableInterface`, but that call resolves to Symfony's real `SubmitButton`, which implements both `ClickableInterface` and `FormInterface`. Added a shared `createClickableFormMock()` helper on `ControllerTestCase` that mocks the concrete `SubmitButton` class instead.
- **`4c84e133`** — `CustomFieldValueTypeTest` mocked `FormBuilderInterface::getName()` to return an `int`; the production code casts it to derive a field ID and expects the numeric-string form real form builders produce.
- **`082d17fd`** — `self::$container` (removed in Symfony 6) → `static::getContainer()`, in the shared `CustomObjectTestCase` base class and its direct test.
- **`41aa0f1b`** — `CustomItemExportSchedulerModel` never created its export directory; harmless if it already existed (e.g. from a prior export), but fatal on a truly fresh install where `@fopen()`'s error-suppression silently returned `false` into `fputcsv()`. Not Symfony-6-specific, just never previously exercised on a fresh instance. Now creates the directory up front if missing.

Notably, `SaveControllerTest.php` already had coverage that POSTs an array-shaped `custom_item` payload through `saveAction()` (the earlier `d2105e6f` fix) — with the suite now running, this branch's own future regressions in that area would be caught by `phpunit` alone, no browser round-trip needed.

## Recommendation

Merge `chore/mautic6-official-verify` into `6.x` — all fixes are small, targeted, independently verified (either via review + re-test, or via direct instantiation through the real DI container for the `TokenSubscriber` fix), and the full unit suite plus both headline end-user flows (Segment filtering, Campaign conditions) now pass against a fresh, official Mautic 6.0.9. A repo-wide grep confirms no single-colon `Class:method` controller-reference strings remain anywhere in `Controller/`.

No action needed regarding the mkcert/HTTPS gap or the migrations-metadata-storage note — both are environment-specific and out of scope for the plugin, but worth keeping in mind for future test runs on this host.
