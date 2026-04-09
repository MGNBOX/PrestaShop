---
name: create-playwright-filter-campaign
brick: E4
component: PlaywrightTesting
step: 11
needs: [E1, E2, G3, H2]
produces: "02_filterSort{Domain}s.ts — filter and sort campaign for the grid"
conditional: false
---

# create-playwright-filter-campaign

## Description
Create the Playwright campaign that verifies all grid filters and sortable columns. Creates known test data, applies each filter, verifies filtered results, resets, and re-verifies.

## Context
- **Brick:** E4 — Step 11
- **Reads from:** E1 (fixtures for predictable test data), G3 (filter names to test)
- **Writes to:** E2 (cleanup in afterAll)
- **Artifact:** `tests/UI/campaigns/functional/BO/{section}/{subsection}/02_filterSort{Domain}s.ts`
- **PS example:** Check existing filterSort campaigns in `tests/UI/`

## Instructions

1. Create 3 entities with distinct values for each filterable field.
2. For each filter:
   a. Enter filter value (text, select, date range).
   b. Submit filter.
   c. Assert only matching rows visible.
   d. Assert non-matching rows absent.
   e. Reset filter, assert all rows visible.
3. For each sortable column: click sort ascending, verify order; click sort descending, verify reverse order.
4. `afterAll`: resetter cleanup.

## Rules

- Create test data with values that produce distinct, verifiable filter results
- Test filter reset as explicitly as filter apply
