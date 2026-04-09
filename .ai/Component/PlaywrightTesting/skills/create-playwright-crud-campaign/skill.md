---
name: create-playwright-crud-campaign
brick: E3
component: PlaywrightTesting
step: 11
needs: [E1, E2, H2, T1, T2]
produces: "01_CRUD{Domain}.ts — end-to-end create → verify → edit → verify → delete lifecycle campaign"
conditional: false
---

# create-playwright-crud-campaign

## Description
Create the most important Playwright campaign covering the full entity lifecycle. Tests create, verify in list, edit, verify edit, and delete — all on real browser pages.

## Context
- **Brick:** E3 — Step 11
- **Reads from:** E1 (test fixtures), H2 (route paths for navigation), T1/T2 (page selectors)
- **Writes to:** E2 (cleanup in afterAll)
- **Artifact:** `tests/UI/campaigns/functional/BO/{number}_{section}/{number}_{subsection}/01_CRUD{Domain}.ts`
- **PS example:** `tests/UI/campaigns/functional/BO/` (check for existing CRUD campaigns)

## Instructions

1. Determine the correct directory under `tests/UI/campaigns/functional/BO/{section}/{subsection}/` — check existing numbering.
2. Import `test`, `expect` from Playwright; import fixtures from E1; import page objects if they exist.
3. `beforeAll`: log in to back office; enable feature flag if still in beta (`await testContext.enableFeatureFlag('{domain}')`).
4. Test "create": navigate to create URL, fill fields using `data{Domain}Minimal`, click save, assert success flash.
5. Test "verify in list": navigate to index, assert entity name visible in grid.
6. Test "edit": navigate to edit URL with entity ID, change name, save, assert success flash.
7. Test "verify edit": confirm updated name visible in grid.
8. Test "delete": click delete row action, confirm modal, assert entity no longer in list.
9. `afterAll`: call `{Domain}Resetter::resetAll()` and log out.

## Rules

- Tests must run in order (Playwright's serial mode: `test.describe.serial`)
- Use `data-test` attributes for selectors where available — not CSS classes
- Assert success flash after every create/edit/delete — never assume success
- The beforeAll feature flag enable is removed at GA (Step 12)
