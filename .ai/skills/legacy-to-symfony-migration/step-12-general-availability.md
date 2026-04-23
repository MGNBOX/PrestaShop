---
step: 12
title: "General Availability"
previous: step-11-playwright-tests.md
next: step-13-legacy-deprecation.md
deliverable: "feature_flag.xml updated to stability='stable' and state='1'; all Playwright tests migrated to use new routes by default"
---

# Step 12 — General Availability

GA is a dedicated PR. It contains no new features — only the flag promotion and the resulting Playwright test cleanup. Mixing feature changes with the GA PR makes the promotion harder to revert if a critical bug is found.

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `promote-feature-flag-to-stable` | `install-dev/data/xml/feature_flag.xml` (edit) | — |
| `write-upgrade-sql` | `upgrade/sql/{version}.sql` | if upgrade path |
| `migrate-playwright-tests-to-default` | edit all Playwright campaign files | — |

## 12.1 — GA prerequisites checklist

Before opening the GA PR, verify every item:

- [ ] All form tabs are complete and reviewable
- [ ] All Behat scenarios pass in CI
- [ ] All Playwright campaigns pass against the flag-enabled configuration
- [ ] QA team has signed off (record the QA sign-off in the PR description)
- [ ] No open P1 bugs against the new pages
- [ ] The legacy controller still works correctly with the flag disabled

## 12.2 — Update `feature_flag.xml`

```xml
<!-- Before -->
<feature_flag id="{domain}">
    <stability>beta</stability>
    <state>0</state>
    ...
</feature_flag>

<!-- After -->
<feature_flag id="{domain}">
    <stability>stable</stability>
    <state>1</state>
    ...
</feature_flag>
```

Two changes, nothing else in this file.

## 12.3 — Database migration (if needed)

For existing installations upgrading to the version containing this GA change, the upgrade scripts must update the flag state. Add a migration or an upgrade SQL file:

```sql
-- upgrade/sql/{version}.sql
UPDATE `ps_feature_flag` SET `state` = 1, `stability` = 'stable' WHERE `name` = '{domain}';
```

Check whether the PS upgrade process auto-syncs `feature_flag.xml` with the DB — if it does, no additional SQL is needed. Verify against the upgrade documentation for the target PS version.

## 12.4 — Migrate Playwright tests to use new routes by default

Before GA, test campaigns used the new pages via the feature flag. After GA, the new pages are the default — tests should no longer need to enable the flag.

For each campaign in `tests/UI/campaigns/functional/BO/.../`:

1. Remove the `await testContext.enableFeatureFlag('{domain}')` call from `beforeAll`
2. Update any hardcoded legacy URLs (`index.php?controller=Admin{Domain}s`) to Symfony routes (`/carriers`, `/carriers/create`, etc.)
3. Update page objects if the HTML structure changed between legacy and new pages
4. Run the full campaign suite to confirm nothing regressed

If a campaign covers **both** legacy and new pages (e.g. testing a feature that redirects from legacy), keep those as separate campaigns rather than conditionally switching.

## 12.5 — Verify legacy controller still loads

After promoting the flag, the legacy controller must still be accessible for shops that may have the flag disabled manually. Test this explicitly:

1. Set `state=0` in DB manually
2. Navigate to `index.php?controller=Admin{Domain}s`
3. Confirm the legacy page loads without errors
4. Re-enable the flag (`state=1`)

This is a hard requirement — never assume the legacy controller can be broken by the GA change.

## 12.6 — PR description template for GA

```markdown
## General Availability: {Domain} page migration

Promotes the `{domain}` feature flag from `beta` to `stable` and enables it by default
for all new/upgraded installations.

### Prerequisites completed
- [x] All form tabs implemented (PRs: #XXXXX, #XXXXX)
- [x] All Behat scenarios pass
- [x] All Playwright campaigns pass
- [x] QA sign-off: @qa_reviewer (date)
- [x] No P1 bugs open

### What changes
- `install-dev/data/xml/feature_flag.xml`: `stability="stable"`, `state="1"`
- Playwright campaigns: feature flag enable call removed; updated to use Symfony URLs by default

### Rollback procedure
If a critical bug is found post-merge:
1. `UPDATE ps_feature_flag SET state=0 WHERE name='{domain}';`
2. The legacy controller serves all traffic immediately (no redeploy needed)
```

## 12.7 — Post-GA monitoring

After the PR is merged and deployed:
- Monitor error logs for any 500s on the new `admin_{domain}s_*` routes
- Check the legacy controller access logs — unexpected traffic there may indicate a broken redirect
- The feature flag can be disabled per-shop in the admin panel if a specific merchant hits a bug

## Checklist

- [ ] All GA prerequisites verified and documented in PR description
- [ ] `feature_flag.xml` updated: `stability="stable"`, `state="1"`
- [ ] Upgrade SQL added if needed to update existing installations
- [ ] All Playwright campaigns updated: feature flag enable call removed, legacy URLs replaced
- [ ] Full Playwright suite passes without the flag enable call
- [ ] Legacy controller manually tested with flag disabled — still works
- [ ] PR description includes QA sign-off and rollback procedure
