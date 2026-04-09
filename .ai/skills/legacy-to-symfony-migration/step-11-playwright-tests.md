---
step: 11
title: "Playwright UI Tests"
skill: legacy-to-symfony-migration
previous: step-10-feature-flag.md
next: step-12-general-availability.md
deliverable: "tests/UI/campaigns/functional/BO/{section}/{subsection}/ test campaigns covering CRUD and every form tab"
---

# Step 11 — Playwright UI Tests

Playwright tests are the gate between beta and GA. They must be written **before** the feature flag is promoted to stable (Step 12). In the Carrier migration, 7 separate campaigns were written across 4 months — start writing them as each form tab is completed, not at the end.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **E1** | `create-test-data-fixtures` | `tests/UI/data/{domain}.ts` | — |
| **E2** | `create-test-resetter` | `tests/Resources/Resetter/{Domain}Resetter.php` | — |
| **E3** | `create-playwright-crud-campaign` | `tests/UI/.../01_CRUD{Domain}.ts` | — |
| **E4** | `create-playwright-filter-campaign` | `tests/UI/.../02_filterSort{Domain}s.ts` | — |
| **E5** | `create-playwright-bulk-campaign` | `tests/UI/.../03_quickEditAndBulkActions.ts` | — |
| **E6** | `create-playwright-position-campaign` | `tests/UI/.../04_changePosition.ts` | if position |
| **E7** | `create-playwright-tab-campaign` | `tests/UI/.../0{N}_{TabName}.ts` ×N | per tab |

> **E1 and E2** can be written as soon as `A3` (the migration manifest) exists — they do not require a working page.

## 11.1 — Directory structure

```
tests/UI/campaigns/functional/BO/{number}_{section}/{number}_{subsection}/
├── 01_CRUD{Domain}.ts
├── 02_filterSort{Domain}s.ts
├── 03_quickEditAndBulkActions.ts
├── 04_changePosition.ts
├── 05_generalSettings.ts         # one campaign per form tab
├── 06_shippingLocationsAndCosts.ts
└── 07_sizeAndWeight.ts
```

File numbering follows the existing conventions in the directory. Check what numbers are already used before creating new files.

## 11.2 — CRUD campaign (`01_CRUD{Domain}.ts`)

This is the most important campaign. It validates the entire create → verify → edit → delete lifecycle.

```typescript
// tests/UI/campaigns/functional/BO/.../01_CRUD{Domain}.ts
import {expect} from '@playwright/test';
import {test} from '@utils/test';
import {dataXxx} from '@data/{domain}';  // test fixtures

test.describe('{Domain} CRUD', async () => {
  test.beforeAll(async ({browserContext}) => {
    // Enable feature flag if needed (PS 9+ with new pages as default, skip this)
    // Log in to back office
  });

  test('should create a {domain} with all required fields', async ({page}) => {
    // Navigate to create page
    await page.goto(global.FO.URL + '/admin/carriers/create');

    // Fill general tab
    await page.fill('[data-test=name-input]', 'Test {Domain}');
    await page.fill('[data-test=tracking-url]', 'https://track.example.com/{{id}}');

    // Switch to shipping tab
    await page.click('[data-tab=shipping]');
    // ... fill shipping fields

    // Submit
    await page.click('[data-test=save-button]');

    // Verify success flash
    await expect(page.locator('.alert-success')).toBeVisible();

    // Verify entity appears in the list
    await page.goto('.../carriers');
    await expect(page.getByText('Test {Domain}')).toBeVisible();
  });

  test('should edit a {domain}', async ({page}) => {
    // Navigate to edit
    // Change the name
    // Save
    // Verify updated value in list
  });

  test('should delete a {domain}', async ({page}) => {
    // Click delete row action
    // Confirm in modal
    // Verify entity no longer appears in list
  });
});
```

## 11.3 — Filter and sort campaign (`02_filterSort{Domain}s.ts`)

```typescript
test.describe('{Domain} grid filtering and sorting', async () => {
  test('should filter by name', async ({page}) => {
    await page.fill('[data-test=filter-name]', 'Test');
    await page.click('[data-test=filter-submit]');
    // Verify only matching rows visible
    // Verify reset filter button clears the filter
  });

  test('should filter by status', async ({page}) => {
    await page.selectOption('[data-test=filter-active]', '1');
    await page.click('[data-test=filter-submit]');
    // Verify all visible rows have active status
  });

  test('should sort by name ascending', async ({page}) => {
    await page.click('[data-test=sort-name-asc]');
    // Verify rows are in alphabetical order
  });
});
```

## 11.4 — Quick edit and bulk actions campaign (`03_quickEditAndBulkActions.ts`)

```typescript
test.describe('{Domain} bulk actions', async () => {
  test('should bulk enable {domain}s', async ({page}) => {
    // Select all via header checkbox
    await page.check('[data-test=select-all]');
    // Click bulk enable
    await page.click('[data-test=bulk-enable]');
    // Verify success flash
    // Verify all rows show active status
  });

  test('should bulk disable {domain}s', async ({page}) => {
    // Similar to bulk enable
  });

  test('should bulk delete {domain}s', async ({page}) => {
    // Select some rows
    // Click bulk delete
    // Confirm in modal
    // Verify deleted rows gone from list
  });

  test('should toggle status via quick edit', async ({page}) => {
    // Click the toggle switch in a row
    // Verify the icon changes
    // Verify no page reload (AJAX)
  });
});
```

## 11.5 — Position change campaign (`04_changePosition.ts`)

Only needed if the entity has drag-and-drop reordering (`PositionColumn` in grid):

```typescript
test('should change {domain} position', async ({page}) => {
  // Get initial positions
  const rows = await page.locator('[data-test=grid-row]').all();
  const firstName = await rows[0].locator('[data-test=name]').textContent();
  const secondName = await rows[1].locator('[data-test=name]').textContent();

  // Drag first row to second position
  await page.dragAndDrop(
    '[data-test=grid-row]:first-child [data-test=position-handle]',
    '[data-test=grid-row]:nth-child(2)'
  );

  // Verify positions swapped
  const newRows = await page.locator('[data-test=grid-row]').all();
  expect(await newRows[0].locator('[data-test=name]').textContent()).toBe(secondName);
  expect(await newRows[1].locator('[data-test=name]').textContent()).toBe(firstName);
});
```

## 11.6 — Per-tab form campaigns (one per tab)

Write one dedicated campaign per form tab. Each campaign:
1. Creates a {domain} with only the minimum required fields (other tests' state should not bleed in)
2. Edits it to fill all fields in this specific tab
3. Saves and verifies the values persisted correctly

```typescript
// 05_generalSettings.ts
test.describe('{Domain} general settings', async () => {
  test('should update name and tracking URL', async ({page}) => { ... });
  test('should upload a logo', async ({page}) => { ... });
  test('should update transit time (multilingual)', async ({page}) => { ... });
  test('should update group access', async ({page}) => { ... });
});

// 06_shippingLocationsAndCosts.ts
test.describe('{Domain} shipping locations and costs', async () => {
  test('should add a shipping zone', async ({page}) => { ... });
  test('should set shipping method to by weight', async ({page}) => { ... });
  test('should add a price range', async ({page}) => { ... });
  test('should set tax rules group', async ({page}) => { ... });
});
```

## 11.7 — Test data utilities

Create test data fixtures in `tests/UI/data/{domain}.ts`:

```typescript
// tests/UI/data/{domain}.ts
export const dataXxx = {
  minimal: {
    name: 'Test {Domain}',
    active: true,
  },
  full: {
    name: 'Full Test {Domain}',
    active: true,
    trackingUrl: 'https://track.example.com/{{id}}',
    grade: 3,
    delay: { en: '2-3 days', fr: '2-3 jours' },
    maxWeight: 30,
    // ...
  },
};
```

Create a `tests/Resources/Resetter/{Domain}Resetter.php` to clean up created test entities between test suites.

## 11.8 — Running against the feature flag

Tests for a beta page need the feature flag enabled. Use the PS test helpers:

```typescript
// In beforeAll hook
await testContext.enableFeatureFlag('{domain}');
```

Or set `state=1` via the database fixture setup.

## Checklist

- [ ] `01_CRUD{Domain}.ts` — full create → verify → edit → delete lifecycle
- [ ] `02_filterSort{Domain}s.ts` — filter by each filterable column + sort
- [ ] `03_quickEditAndBulkActions.ts` — bulk enable, bulk disable, bulk delete, toggle quick-edit
- [ ] `04_changePosition.ts` — drag-and-drop reorder (if applicable)
- [ ] One campaign per form tab — each verifies all fields in that tab persist correctly
- [ ] Test data fixtures in `tests/UI/data/{domain}.ts`
- [ ] `{Domain}Resetter.php` created for test cleanup
- [ ] All campaigns run against the feature flag enabled
- [ ] All campaigns green before requesting flag promotion to stable (Step 12)
