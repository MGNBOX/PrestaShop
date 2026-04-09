---
step: 13
title: "Legacy Deprecation"
skill: legacy-to-symfony-migration
previous: step-12-general-availability.md
next: null
deliverable: "Deprecation notice in AdminXxxController.php; legacy controller scheduled for removal in a future major release"
---

# Step 13 — Legacy Deprecation

This step happens **6–12 months after GA** (not immediately). The legacy controller is never deleted at GA time. Merchants running on older versions, third-party modules hooking into the legacy controller, and upgrade paths all depend on it remaining functional. Deprecation is a signal, not a removal.

The Carrier migration added this step 5 months after GA (PR #39050, July 2025, GA was February 2025).

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **R4** | `add-legacy-deprecation-notice` | `controllers/admin/Admin{Domain}sController.php` (edit) | — |
| **R5** | `write-changelog-deprecation` | `CHANGELOG.md` (edit) | — |
| **R6** | `create-removal-issue` | GitHub Issue | — |

## 13.1 — When to deprecate

Trigger this step when:
- The new Symfony pages have been stable for at least one minor release cycle
- No P1 bugs have been filed against the new pages in the past 2 releases
- The team has confirmed no core modules rely on the legacy controller directly
- The next **major** release is being planned (deprecation targets that major)

Do not deprecate:
- In the same PR as GA
- In a patch or minor release where the legacy controller is still the fallback for some merchants
- Before third-party module developers have had time to migrate

## 13.2 — Add a deprecation notice to the legacy controller

Open `controllers/admin/Admin{Domain}sController.php`. At the top of the `init()` method (or `__construct()` if it exists), add a visible admin notice:

```php
public function init(): void
{
    parent::init();

    // Display migration notice if the new page is available
    if ($this->isNewPageAvailable()) {
        $this->warnings[] = $this->trans(
            'This page will be removed in a future version. Please switch to the new %link%{Domain}s management page%/link%.',
            [
                '%link%' => sprintf('<a href="%s">', $this->context->link->getAdminLink('admin_{domain}s_index')),
                '%/link%' => '</a>',
            ],
            'Admin.Notifications.Warning'
        );
    }
}

private function isNewPageAvailable(): bool
{
    // Check if the feature flag is stable and the new page exists
    return (bool) \Configuration::get('PS_FEATURE_FLAG_{DOMAIN}');
    // Or use the FeatureFlagRepository if available
}
```

The notice must:
- Link directly to the new page URL
- Be in the `$this->warnings[]` array (renders as a yellow banner, not a blocking error)
- Be translatable
- Only show when the new page is actually available (flag is on)

## 13.3 — Announce removal in changelog

Add an entry to `CHANGELOG.md` (or the equivalent for the PS version):

```markdown
### Deprecated
- `AdminXxxController` is deprecated and will be removed in PrestaShop X.0.
  Use the Symfony `{Domain}Controller` and the new admin routes instead.
```

If there is a developer-facing migration guide or upgrade notes document, add the deprecation there too.

## 13.4 — Notify module developers

For core pages with known third-party hook usage, open a GitHub issue or post in the developer Slack/forum:

> **[Deprecation notice]** `Admin{Domain}sController` will be removed in PS X.0.
> If your module hooks into `displayAdmin{Domain}sListBefore`, `actionAdmin{Domain}sControllerXxx`, or similar hooks on this controller, please migrate to the new `{domain}.*.before`/`{domain}.*.after` hooks dispatched by the Symfony controller.

List the hooks that the legacy controller dispatches and their new equivalents in the Symfony controller (if they exist) or note that they need to be re-implemented.

## 13.5 — Schedule removal

Create a GitHub issue for the actual removal, targeting the next major version:

```markdown
**Title:** Remove deprecated `Admin{Domain}sController`

**Target:** PS X.0

**Prerequisites:**
- [ ] All known modules have been updated
- [ ] Migration guide published
- [ ] Deprecation has been in place for at least 2 minor releases

**Files to delete:**
- `controllers/admin/Admin{Domain}sController.php`
- Any legacy-only service registrations referencing this controller
- Legacy-only hooks that have no Symfony equivalent

**Files to update:**
- Remove `_legacy_controller: Admin{Domain}s` from routing YAML
- Remove `_legacy_feature_flag: {domain}` from routing YAML (or remove the flag check entirely)
- Clean up `feature_flag.xml` entry if no longer needed
```

Tag the issue with the target milestone and assign it to the team responsible for the major release cleanup.

## 13.6 — What NOT to do

- Do **not** throw PHP deprecation warnings (`@trigger_error()`) from the legacy controller — it causes noise in merchant logs and they can't fix it
- Do **not** remove the legacy controller in a minor or patch release
- Do **not** disable the legacy controller — it must remain callable as long as the flag can be toggled off
- Do **not** break existing module hooks on the legacy controller without a migration path

## Checklist

- [ ] At least 6 months since GA, no P1 bugs, stable release confirmed
- [ ] Deprecation notice added to `Admin{Domain}sController::init()` — visible banner with link to new page
- [ ] Notice only shown when new page is available (feature flag check)
- [ ] `CHANGELOG.md` updated with deprecation entry
- [ ] Developer announcement published (GitHub issue, Slack, forum)
- [ ] Legacy hooks documented with their Symfony equivalents
- [ ] GitHub issue created for removal targeting next major version with all prerequisites listed
