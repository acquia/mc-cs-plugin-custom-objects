# Mautic 6 Upgrade Design

**Date:** 2026-03-06
**Status:** Approved
**Approach:** Boot-test driven (consistent with ADR-0001 Decision 1)

---

## Context

The `WebAnyOne/mautic` fork and `WebAnyOne/mc-cs-plugin-custom-objects` plugin are currently targeting Mautic 5.2 (`webanyone/staging` branch). Mautic 6.0.7 (Orion Edition) is the current LTS release, requiring Symfony 6 and dropping several APIs removed from Mautic 5.

Upstream `acquia/mc-cs-plugin-custom-objects` uses one branch per major Mautic version (`staging` = Mautic 4, `5.x` = Mautic 5). We follow the same convention.

---

## Repository Structure

| Repo | New branch | Base | Target |
|------|-----------|------|--------|
| `WebAnyOne/mautic` | `6.0` | `mautic/mautic@6.0` | Mautic 6 distribution with plugin declared |
| `WebAnyOne/mc-cs-plugin-custom-objects` | `6.x` | `webanyone/staging` | Plugin migrated to Mautic 6 |

`webanyone/staging` remains unchanged (Mautic 5 target).

---

## Known Breaking Changes (Mautic 5 → 6)

From `UPGRADE-6.0.md` and code audit of the plugin:

| Issue | Files affected | Severity |
|-------|---------------|----------|
| `MauticFactory` removed | `CustomObjectsBundle.php`, `ControllerTestCase.php` | Fatal (if called) |
| `PluginBundleBase::installPluginSchema` API changed | `CustomObjectsBundle.php` | Fatal |
| Symfony 6: `request->get()` no longer returns arrays | ~10 controller files (30 call sites) | Runtime |
| `composer.json` must target `mautic/core-lib: ^6.0` | `composer.json` | Build |

`SessionInterface`, `Criteria::ASC/DESC`, and `tightenco/collect` usages were audited and confirmed absent from the plugin.

---

## Execution Plan

### Step 1 — Plugin: create `6.x` branch
- Branch `6.x` from `webanyone/staging`
- Update `composer.json`: `mautic/core-lib: ^5.0` → `^6.0`
- Push to `WebAnyOne/mc-cs-plugin-custom-objects`

### Step 2 — Mautic fork: create `6.0` branch
- Branch `6.0` from upstream `mautic/mautic@6.0` (fetched from `origin`)
- Apply `composer.json` changes:
  - Add VCS repository pointing to `WebAnyOne/mc-cs-plugin-custom-objects`
  - Add `"acquia/mc-cs-plugin-custom-objects": "dev-6.x"` to `require`
  - Add specific `installer-paths` entry for `plugins/CustomObjectsBundle`
- Run `composer update acquia/mc-cs-plugin-custom-objects --no-install` to lock
- Push to `WebAnyOne/mautic`

### Step 3 — Local environment
- Clone `WebAnyOne/mautic@6.0` into `/tmp/webanyone-mautic-6`
- Run `composer install --no-scripts`
- Configure DDEV pointing at `/tmp/webanyone-mautic-6`
- Install Mautic (database setup)

### Step 4 — Boot-test iteration
- Run `ddev exec bin/console cache:clear`
- Fix each fatal error one at a time
- Repeat until app boots cleanly

### Step 5 — Browser verification
- Log in at `https://webanyone-mautic-6.ddev.site`
- Navigate to Custom Objects
- Confirm plugin renders correctly

### Step 6 — Commit and push
- Commit all plugin fixes to `WebAnyOne/mc-cs-plugin-custom-objects@6.x`
- Update `composer.lock` in `WebAnyOne/mautic@6.0`
- Push both branches

---

## Success Criteria

- `cache:clear` runs without fatal errors
- Mautic admin UI loads and is navigable
- Custom Objects list page renders
- Creating/viewing a custom object works
