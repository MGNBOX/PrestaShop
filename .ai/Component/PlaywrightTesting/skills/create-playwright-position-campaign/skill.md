---
name: create-playwright-position-campaign
brick: E6
component: PlaywrightTesting
step: 11
needs: [E1, E2, G1]
produces: "04_changePosition.ts — drag-and-drop position reorder campaign"
conditional: "only if domain has PositionColumn in grid (G1)"
---

# create-playwright-position-campaign

## Description
Create the campaign that verifies drag-and-drop row reordering in the grid. Reads initial positions, drags row 1 to position 2, and verifies the new order.

## Context
- **Brick:** E6 — Step 11
- **Reads from:** E1 (fixtures for distinct names), E2 (resetter), G1 (PositionColumn presence)
- **Writes to:** E2 (cleanup in afterAll)
- **Artifact:** `tests/UI/campaigns/functional/BO/{section}/{subsection}/04_changePosition.ts`
- **PS example:** Check any PS grid with drag-and-drop reordering in `tests/UI/`

## Instructions

1. Create at least 3 entities with distinct names.
2. Read initial order: get all row names in order.
3. Drag first row to second position using `page.dragAndDrop()`.
4. Verify row order changed: first name is now second, second is now first.
5. Reload page and verify new order persisted to DB.
6. `afterAll`: resetter.

## Rules

- Always verify persistence by reloading the page after drag
- Use `page.dragAndDrop()` with the position drag handle selector
