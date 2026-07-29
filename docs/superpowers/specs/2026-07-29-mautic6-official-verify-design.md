# Verify CustomObjectsBundle Against a Fresh Official Mautic 6 Instance

**Date:** 2026-07-29
**Status:** Approved
**Approach:** Boot-test driven, iterative crash-first (consistent with `docs/adr/0001-mautic5-migration-strategy.md` Decision 1, present on the `6.x`/`fix/dynamic-content-subscriber-mautic6` plugin branches)

---

## Context

This plugin already has Mautic-6-targeted work on the `6.x` branch, developed and boot-tested against the `WebAnyOne/mautic` fork's `6.0` branch (see `docs/plans/2026-03-06-mautic6-upgrade-design.md`, also on the `6.x` branch history). That fork carries fork-specific customizations on top of upstream Mautic, which can mask or introduce compatibility differences that wouldn't show up against a real, unmodified Mautic 6 install.

Separately, the current branch (`fix/dynamic-content-subscriber-mautic5`) has newer fixes not yet ported to any Mautic-6-targeted branch, including a plugin-schema-install fix (`fix(install): create plugin schema via the supported event API`) that touches exactly the kind of install-time code path a fresh instance would exercise.

The goal of this pass: confirm — and where needed, fix — that the plugin boots and works against a **fresh, official, unmodified Mautic 6 instance** (`mautic/mautic` upstream, not the fork), starting from the `6.x` branch as the baseline.

---

## Approach

### Isolation via git worktrees

Both the Mautic core checkout (`~/WS/webanyone/mautic`, currently on `5.2` with local uncommitted tweaks and a live dev DB) and the plugin checkout (this directory, currently on `fix/dynamic-content-subscriber-mautic5` with in-progress work) must stay untouched. Git worktrees give isolated working directories without disturbing either:

- **Mautic core:** `git worktree add ../mautic-6-official 6.0.9`, detached at the latest stable 6.x tag, off the existing `upstream` remote (`mautic/mautic`) already fetched into `~/WS/webanyone/mautic`. No new clone needed.
- **Plugin:** `git worktree add ../mc-cs-plugin-custom-objects.mautic6-official -b chore/mautic6-official-verify 6.x`, a new branch off `6.x`, so any tweaks land as a reviewable diff against `6.x` rather than mixing into the current branch's history.

### Wiring the plugin into the fresh instance

Add a `web_mounts` entry to the fresh Mautic worktree's `.ddev/config.yaml`, bind-mounting the plugin worktree directly at `plugins/CustomObjectsBundle`. This is the "long-term resolution" the existing ADR (Decision 3) identified but deferred: a real bind mount, unlike a symlink, resolves correctly via `realpath()` inside the Docker container, so Doctrine entity mapping and bundle discovery work without a copy step. Composer is not involved in installing the plugin package — Mautic discovers bundles under `plugins/` directly — so no VCS repository or `composer.json` version constraint juggling is needed for this test.

This is a local-only modification to the fresh worktree's DDEV config (uncommitted, throwaway); it doesn't touch the plugin's own `composer.json` or the fork.

### Boot and iterate

`ddev start` in the fresh Mautic worktree runs its own (upstream-provided) non-interactive post-start hook: `composer install` → `mautic:install` → `cache:warmup` → `mautic:plugins:reload`.

Following the same method already validated in this repo's ADR-0001 (Decision 1: iterative crash-first debugging, not a full upfront audit): boot, observe the first fatal error (if any), fix it in the plugin worktree, re-run, repeat until:

- `cache:clear`/`cache:warmup` completes with no fatal errors
- `mautic:plugins:reload` installs the plugin (including schema) without error
- The admin UI loads and is navigable (`admin` / `Maut1cR0cks!`)
- The Custom Objects list page renders
- Creating a Custom Object and a Custom Item works end-to-end

A likely early failure: the `6.x` branch predates the current branch's `fix(install): create plugin schema via the supported event API` commit, so plugin schema installation may fail the same way that fix addressed. If so, port that fix (and only that fix, per crash-first scope discipline — not the other four pending commits, which are a separate concern from this verification pass).

### Out of scope

- Porting the full set of pending mautic5-branch commits (FilterEvaluator refactor, evaluator int-type fix, new unit tests) to the Mautic 6 branches. Only port what's needed to unblock a boot/install failure encountered here.
- Testing against the `WebAnyOne/mautic` fork or its `6.0` branch.
- Merging `chore/mautic6-official-verify` back into `6.x` — that's a follow-up decision once the pass is complete and findings are reviewed.
- Tearing down the DDEV environment / worktrees automatically at the end — leave them running for follow-up inspection unless told otherwise.

---

## Success Criteria

Same five checks listed above under "Boot and iterate." Report is a pass/fail per criterion, with root cause and fix (if any) for each failure encountered along the way.
