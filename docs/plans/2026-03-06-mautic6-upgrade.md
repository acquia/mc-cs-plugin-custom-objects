# Mautic 6 Upgrade Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate `WebAnyOne/mc-cs-plugin-custom-objects` and `WebAnyOne/mautic` from Mautic 5.2 to Mautic 6.0, verified by a running DDEV instance.

**Architecture:** Boot-test driven (ADR-0001 Decision 1). Create `6.x` plugin branch and `6.0` Mautic fork branch, fix known fatal errors pre-emptively, then iterate on `cache:clear` until the app boots, then verify in the browser.

**Tech Stack:** PHP 8.3, Symfony 6, Mautic 6.0.7, Composer 2, DDEV, PHPUnit 9.5

---

### Task 1: Create plugin `6.x` branch

**Repos touched:**
- `WebAnyOne/mc-cs-plugin-custom-objects` — working dir: `/home/edouard/WS/webanyone/mc-cs-plugin-custom-objects`

**Step 1: Create and switch to `6.x` branch from `staging`**

```bash
cd /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects
git checkout staging
git pull webanyone staging
git checkout -b 6.x
```

**Step 2: Update `composer.json` — bump core-lib to Mautic 6**

In `composer.json`, change:
```json
"mautic/core-lib": "^5.0"
```
to:
```json
"mautic/core-lib": "^6.0"
```

**Step 3: Commit**

```bash
git add composer.json
git commit -m "feat: target mautic/core-lib ^6.0 for Mautic 6 compatibility"
```

---

### Task 2: Fix `CustomObjectsBundle.php` — remove dead MauticFactory code

**Files:**
- Modify: `CustomObjectsBundle.php`

**Context:** In Mautic 6, `MauticFactory` was fully removed. `PluginBundleBase` is now an empty class. The `installAllTablesIfMissing()` method is dead code (never called by Mautic 6) and its `MauticFactory` type hint will cause a fatal PHP error during Symfony's DI cache build if not removed.

**Step 1: Write a test that confirms the class loads without MauticFactory**

In `Tests/Unit/CustomObjectsBundleTest.php` (create if it doesn't exist):

```php
<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit;

use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomObjectsBundleTest extends TestCase
{
    public function testBundleBuildsWithoutError(): void
    {
        $bundle = new CustomObjectsBundle();
        $container = new ContainerBuilder();
        $bundle->build($container);
        $this->assertTrue(true); // No fatal error thrown
    }
}
```

**Step 2: Run test to verify it fails** (MauticFactory not found)

```bash
cd /home/edouard/WS/webanyone/mautic
bin/phpunit -d memory_limit=1G --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist \
  plugins/CustomObjectsBundle/Tests/Unit/CustomObjectsBundleTest.php
```

Expected: FAIL or ERROR — `Class "Mautic\CoreBundle\Factory\MauticFactory" not found`

> Note: This test may only fail once the Mautic 6 branch is wired (Task 4). If it passes on Mautic 5, skip ahead and verify it still passes after switching to Mautic 6 in Task 6.

**Step 3: Remove `installAllTablesIfMissing` and the `MauticFactory` import**

Replace the entire `CustomObjectsBundle.php` with:

```php
<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomObjectsBundle extends AbstractPluginBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomFieldTypePass());
    }
}
```

**Step 4: Run test to verify it passes**

```bash
bin/phpunit -d memory_limit=1G --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist \
  plugins/CustomObjectsBundle/Tests/Unit/CustomObjectsBundleTest.php
```

Expected: `OK (1 test, 1 assertion)`

**Step 5: Commit**

```bash
cd /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects
git add CustomObjectsBundle.php Tests/Unit/CustomObjectsBundleTest.php
git commit -m "fix: remove MauticFactory dependency removed in Mautic 6"
```

---

### Task 3: Fix `ControllerTestCase.php` — remove MauticFactory mock

**Files:**
- Modify: `Tests/Unit/Controller/ControllerTestCase.php`

**Context:** This test base class mocks `MauticFactory` which no longer exists in Mautic 6. Remove the mock and any constructor injection of it.

**Step 1: Check what uses `$this->mauticFactory` in test subclasses**

```bash
grep -rn "mauticFactory" /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects/Tests/ --include="*.php"
```

Review the output. If `$this->mauticFactory` is passed into constructors of classes under test, those constructors may also need updating.

**Step 2: Remove MauticFactory from ControllerTestCase**

In `Tests/Unit/Controller/ControllerTestCase.php`:
- Remove `use Mautic\CoreBundle\Factory\MauticFactory;`
- Remove `/** @var MockObject|MauticFactory */ private $mauticFactory;` property
- Remove `$this->mauticFactory = $this->createMock(MauticFactory::class);` from `setUp()`
- Remove any `$this->mauticFactory` argument from constructor calls in `setUp()`

**Step 3: Run the controller test suite**

```bash
cd /home/edouard/WS/webanyone/mautic
bin/phpunit -d memory_limit=1G --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist \
  plugins/CustomObjectsBundle/Tests/Unit/Controller/ 2>&1 | grep -E "^(OK|ERRORS|FAILURES|Tests:)"
```

Expected: Same or better result than before (no new failures introduced).

**Step 4: Commit**

```bash
cd /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects
git add Tests/Unit/Controller/ControllerTestCase.php
git commit -m "fix: remove MauticFactory mock from ControllerTestCase (removed in Mautic 6)"
```

**Step 5: Push `6.x` branch to WebAnyOne**

```bash
git push webanyone 6.x
```

---

### Task 4: Create `WebAnyOne/mautic@6.0` branch

**Repo:** `/home/edouard/WS/webanyone/mautic`

**Step 1: Fetch latest upstream and create `6.0` branch**

```bash
cd /home/edouard/WS/webanyone/mautic
git fetch origin
git checkout -b 6.0 origin/6.0
```

**Step 2: Update `composer.json` — wire the plugin**

Make three changes to `composer.json`:

1. In `repositories` array, add at the top:
```json
{
  "type": "vcs",
  "url": "https://github.com/WebAnyOne/mc-cs-plugin-custom-objects"
},
```

2. In `require`, add:
```json
"acquia/mc-cs-plugin-custom-objects": "dev-6.x"
```

3. In `extra.installer-paths`, add before the generic `"plugins/{$name}"` entry:
```json
"plugins/CustomObjectsBundle": [
  "acquia/mc-cs-plugin-custom-objects"
],
```

**Step 3: Update composer.lock**

```bash
COMPOSER_MEMORY_LIMIT=-1 composer update acquia/mc-cs-plugin-custom-objects --no-install --no-scripts 2>&1 | tail -5
```

Expected: `Lock file updated` with the plugin pinned to the latest commit on `dev-6.x`.

**Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "feat: install Custom Objects plugin via Composer (Mautic 6 / 6.x branch)"
```

**Step 5: Push to WebAnyOne**

```bash
git push webanyone 6.0
```

---

### Task 5: Clone and set up local environment

**Step 1: Clone the Mautic 6 fork**

```bash
rm -rf /tmp/webanyone-mautic-6
git clone --depth 1 --branch 6.0 git@github.com:WebAnyOne/mautic.git /tmp/webanyone-mautic-6
```

**Step 2: Install Composer dependencies (no scripts)**

```bash
cd /tmp/webanyone-mautic-6
COMPOSER_MEMORY_LIMIT=-1 composer install --no-scripts --no-interaction 2>&1 | tail -10
```

Expected: Completes without error. Verify plugin installed:
```bash
ls plugins/CustomObjectsBundle/CustomObjectsBundle.php
```

**Step 3: Configure DDEV**

```bash
cd /tmp/webanyone-mautic-6
ddev config --project-name=webanyone-mautic-6 --php-version=8.3 --docroot=. 2>&1
```

Copy an existing local config if available:
```bash
# If a .ddev/config.local.yaml exists in the main project, adapt it
cp /home/edouard/WS/webanyone/mautic/.ddev/config.yaml .ddev/config.yaml
# Update project name in .ddev/config.yaml if needed
```

**Step 4: Start DDEV**

```bash
ddev start 2>&1 | tail -10
```

Expected: All containers start. Note the URL (e.g., `https://webanyone-mautic-6.ddev.site`).

**Step 5: Install Mautic (DB + config)**

```bash
ddev exec bin/console mautic:install \
  --db_host=db --db_name=db --db_user=db --db_password=db \
  --admin_email=admin@example.com --admin_password="Maut1cR0cks!" \
  http://webanyone-mautic-6.ddev.site 2>&1 | tail -20
```

Or copy the parameters file from the working instance:
```bash
ddev exec cp /var/www/html/config/local.php.dist /var/www/html/config/local.php
# Then edit DB credentials
```

---

### Task 6: Boot-test iteration

**Goal:** `cache:clear` runs without fatal errors.

**Step 1: Run cache:clear**

```bash
cd /tmp/webanyone-mautic-6
ddev exec bin/console cache:clear 2>&1
```

**Step 2: For each fatal error, fix the root cause**

Common errors to expect:
- `Class "Mautic\CoreBundle\Factory\MauticFactory" not found` → already fixed in Task 2; if it appears from another file, grep and remove
- `Class "Symfony\Component\HttpFoundation\Session\SessionInterface" not found` → replace with `RequestStack` pattern
- `Argument must be of type X, Y given` → method signature changed in Mautic 6; adjust to match

Pattern for each fix:
1. Identify the file and line from the stack trace
2. Make the minimal change to resolve the error
3. Re-run `cache:clear`
4. Repeat until clean

**Step 3: Commit all boot fixes together**

```bash
cd /home/edouard/WS/webanyone/mc-cs-plugin-custom-objects
git add -p   # stage only plugin file changes
git commit -m "fix: resolve Mautic 6 boot errors"
git push webanyone 6.x
```

Then update composer.lock in the Mautic fork:
```bash
cd /tmp/webanyone-mautic-6
COMPOSER_MEMORY_LIMIT=-1 composer update acquia/mc-cs-plugin-custom-objects --no-install --no-scripts
git add composer.lock
git commit -m "chore: update plugin lock to include Mautic 6 boot fixes"
git push webanyone 6.0
```

---

### Task 7: Browser verification

**Step 1: Open Playwright and navigate to the instance**

```
http://127.0.0.1:<port>/s/login
```

Find the port:
```bash
docker ps | grep webanyone-mautic-6-web
```

**Step 2: Log in**

Credentials: `admin` / `Maut1cR0cks!`

**Step 3: Verify Custom Objects**

Navigate to `/s/custom/object`.

Expected: Custom Objects list page renders.

**Step 4: Create a test custom object**

Click "New", fill in a name (e.g., "Product"), save.

Expected: Redirects to the object detail view with "Active" status badge.

**Step 5: Take a screenshot as evidence**

Save to `mautic6-custom-objects.png`.

---

### Task 8: Final state — update composer.lock and push

**Step 1: Ensure `composer.lock` in `WebAnyOne/mautic@6.0` pins the final plugin commit**

```bash
cd /tmp/webanyone-mautic-6
COMPOSER_MEMORY_LIMIT=-1 composer update acquia/mc-cs-plugin-custom-objects --no-install --no-scripts
git add composer.lock
git diff --cached composer.lock | grep '"reference"'
```

Expected: Reference points to the latest commit on `webanyone/6.x`.

**Step 2: Push final state**

```bash
git push webanyone 6.0
```

**Step 3: Verify the install from a clean clone**

```bash
rm -rf /tmp/verify-mautic-6
git clone --depth 1 --branch 6.0 git@github.com:WebAnyOne/mautic.git /tmp/verify-mautic-6
cd /tmp/verify-mautic-6
COMPOSER_MEMORY_LIMIT=-1 composer install --no-scripts --no-interaction 2>&1 | tail -5
ls plugins/CustomObjectsBundle/CustomObjectsBundle.php
```

Expected: Plugin installed cleanly with no `MauticFactory` reference in the installed file.
