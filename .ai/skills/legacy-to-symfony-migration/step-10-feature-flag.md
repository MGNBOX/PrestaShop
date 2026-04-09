---
step: 10
title: "Feature Flag Registration"
skill: legacy-to-symfony-migration
previous: step-09-twig-templates.md
next: step-11-playwright-tests.md
deliverable: "feature_flag.xml entry with stability='beta' and state='0'; all routes confirmed to respect the flag"
---

# Step 10 — Feature Flag Registration

The feature flag is **not optional**. It is the mechanism that routes HTTP requests to either the new Symfony controller or the legacy controller. Without this registration, every route carrying `_legacy_feature_flag: {domain}` will throw a 500 error because the flag resolver cannot find the flag definition.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **H3** | `register-feature-flag` | `install-dev/data/xml/feature_flag.xml` (new entry) | — |

> **H3 must be committed together with H1 (`create-admin-controller`) and H2 (`create-admin-routing`)** — routes referencing an unregistered feature flag cause a 500.

## 10.1 — What the feature flag does

When PrestaShop processes a request for a route with `_legacy_feature_flag: {domain}`:

1. It looks up the `{domain}` flag in the `ps_feature_flag` table (populated from `feature_flag.xml` at install/upgrade)
2. **Flag enabled** (`state=1`): request handled by the Symfony controller declared in `_controller`
3. **Flag disabled** (`state=0`): request redirected to the legacy `_legacy_controller` value

This means the flag controls **routing**, not just a UI element. Both controllers must work independently — the flag switches between them without any shared state.

## 10.2 — Register the flag

Open `install-dev/data/xml/feature_flag.xml`. Add an entry:

```xml
<!-- install-dev/data/xml/feature_flag.xml -->
<entities>
  <!-- ... existing entries ... -->
  <feature_flag id="{domain}">
    <stability>beta</stability>
    <state>0</state>
    <!-- Optional: label and description for the admin panel display -->
    <label_wording>{Domain} page</label_wording>
    <label_domain>Admin.{Section}.Feature</label_domain>
    <description_wording>Enable the new Symfony-based {domain} management page (beta).</description_wording>
    <description_domain>Admin.{Section}.Feature</description_domain>
  </feature_flag>
</entities>
```

Attribute values:
- `id="{domain}"` — must match the value used in `_legacy_feature_flag: {domain}` in all routes exactly (case-sensitive, lowercase, no spaces)
- `stability="beta"` — shown in the admin panel under "New & Experimental Features" with a "Beta" badge
- `state="0"` — disabled by default on new installs; contributors enable it manually during development

## 10.3 — How the flag table is populated

The `feature_flag.xml` file is consumed in two situations:

1. **Fresh install** — the installer reads the XML and inserts rows into `ps_feature_flag`
2. **Upgrade** — the upgrade scripts sync the XML with the table (adds new flags, does not reset existing ones)

**During development**, after adding the XML entry, run the installer fixtures or manually insert the row:

```sql
INSERT INTO `ps_feature_flag` (`name`, `state`, `stability`, `label_wording`, `label_domain`, `description_wording`, `description_domain`)
VALUES ('{domain}', 0, 'beta', '', '', '', '');
```

Or toggle it from the admin panel: **Advanced Parameters → New & Experimental Features**.

## 10.4 — Enable for development/testing

To test the new Symfony pages locally, enable the flag:

```sql
UPDATE `ps_feature_flag` SET `state` = 1 WHERE `name` = '{domain}';
```

Or via admin: **Advanced Parameters → New & Experimental Features → enable "{Domain} page"**.

After enabling:
- Visiting the legacy URL (`index.php?controller=Admin{Domain}s`) redirects to the Symfony route
- The Symfony route is now accessible

After disabling:
- The Symfony route redirects back to the legacy controller

## 10.5 — Verify routes respect the flag

Run the router debugger to confirm routes are registered with the flag:

```bash
php bin/console debug:router | grep {domain}
```

Expected output should show all routes with `_legacy_feature_flag` in their defaults. Then verify the flag fallback by:

1. Disabling the flag in DB
2. Navigating to `index.php?controller=Admin{Domain}s`
3. Confirming the legacy page loads
4. Enabling the flag
5. Navigating to the same URL
6. Confirming redirect to the new Symfony URL

## 10.6 — Relation to the GA step

The flag starts as `stability="beta"` and `state="0"`. It is promoted to `stability="stable"` and `state="1"` in a dedicated PR (Step 12) only after:
- All form tabs are complete
- All Playwright tests pass
- QA has signed off

Never promote the flag to stable in the same PR that adds features — keep the stability promotion as its own atomic change.

## Checklist

- [ ] `feature_flag.xml` entry added with `id="{domain}"`, `stability="beta"`, `state="0"`
- [ ] Label and description wordings added for the admin panel display
- [ ] Fresh DB populated: flag row exists in `ps_feature_flag` (via installer or manual SQL)
- [ ] Flag enabled locally for development testing
- [ ] Legacy controller still reachable with flag disabled
- [ ] Symfony controller reachable with flag enabled
- [ ] `php bin/console debug:router | grep {domain}` shows all expected routes
- [ ] PR description notes the flag name so reviewers know how to test
