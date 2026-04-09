---
name: create-grid-definition
brick: G1
component: Grid
step: 4
needs: [A3]
produces: "{Domain}GridDefinitionFactory.php — defines columns, filters, actions for the entity listing"
conditional: false
---

# create-grid-definition

## Description
Create the Grid Definition Factory that declares all columns, bulk actions, and row actions for the entity listing page. This factory is the single source of truth for the grid structure.

## Context
- **Brick:** G1 — Step 4
- **Reads from:** A3 manifest Section 4 (grid columns), Section 5 (filters), Section 4 (actions)
- **Writes to:** G3 (grid filters reference this), G4 (actions defined here), H1 (controller uses grid definition), T1 (template renders grid)
- **Artifact:** `src/Core/Grid/Definition/Factory/{Domain}GridDefinitionFactory.php`
- **PS example:** `src/Core/Grid/Definition/Factory/CarrierGridDefinitionFactory.php`

## Instructions

1. Create `{Domain}GridDefinitionFactory.php` extending `AbstractGridDefinitionFactory`.
2. Override `getId(): string` — return a unique grid ID (e.g., `'carrier'`).
3. Override `getName(): string` — return translatable grid name.
4. Implement `getColumns(): ColumnCollection` — add each column from A3 manifest using column type classes.
5. For each column, choose the correct type: `DataColumn` (text), `ToggleColumn` (boolean with AJAX toggle), `ActionColumn` (row actions), `PositionColumn` (drag-and-drop reorder, if applicable).
6. Implement `getFilters(): FilterCollection` — add filters matching the columns.
7. Implement `getBulkActions(): BulkActionCollection` — add enable, disable, delete bulk actions.
8. Implement `getRowActions(): RowActionCollection` — add edit and delete row actions.
9. Register the factory in DI (G5).

## Rules

- Every column must have a unique ID matching the QueryBuilder alias (G2)
- ToggleColumn requires an AJAX route in the actions config
- PositionColumn is only added when the entity supports drag-and-drop reordering
- Column IDs must be snake_case matching the SQL query result column alias
