---
name: create-position-column
brick: —
component: Grid
step: 4
needs: [G1, H2]
produces: "PositionColumn configuration and position-update route wiring"
conditional: "only for entities with position/sort support"
---

# create-position-column

## Description
Documents how to add drag-and-drop row reordering to a PrestaShop grid. Requires a PositionColumn in the definition, a dedicated update-position route, and position handling in the repository.

## Context
- **Brick:** — — Step 4
- **Reads from:** G1 (factory to edit), H2 (routes to add)
- **Writes to:** G1 (add PositionColumn), H2 (add update-position route), repository (position persistence)
- **Artifact:** Grid Definition Factory (edit) + new route in H2
- **PS example:** Check any grid with drag-and-drop reordering (e.g., CMS pages, carrier list if ordered)

## Instructions

1. Add `PositionColumn` as the second column (after BulkActionColumn) in the Grid Definition.
2. Configure the position update route: `->setOption('update_method', 'POST')->setOption('update_route', 'admin_{domain}s_update_position')`.
3. Create the `admin_{domain}s_update_position` POST route in H2.
4. In the controller, handle the AJAX position update: receive `positions[]` array, dispatch `UpdatePosition{Domain}Command` (or use QueryBuilder directly).
5. In the repository, update the `position` column for the moved entities.

## Rules

- Position updates are always AJAX — return JSON response, not redirect
- The position column should reflect the actual DB `position` field
- Position values start at 0 or 1 — be consistent with existing PS convention
