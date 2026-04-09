---
name: create-grid-row-actions
brick: —
component: Grid
step: 4
needs: [G1, H2]
produces: "RowActionCollection with edit and delete actions wired to correct routes"
conditional: false
---

# create-grid-row-actions

## Description
Documents the PrestaShop row action types (edit link, delete link with confirmation, custom actions) and the correct routing configuration.

## Context
- **Brick:** — — Step 4
- **Reads from:** G1 (factory to edit), H2 (route names to reference)
- **Writes to:** G1 (row actions are defined inside the Grid Definition Factory)
- **Artifact:** Grid Definition Factory `getRowActions()` method
- **PS example:** `src/Core/Grid/Definition/Factory/CarrierGridDefinitionFactory.php`

## Instructions

1. `LinkRowAction` for edit: links to `admin_{domain}s_edit` with `{id}` route parameter.
2. `LinkRowAction` for delete: links to `admin_{domain}s_delete`, with `confirm_message` option for JS confirmation.
3. Pass the row's primary key as the `{id}` route parameter.
4. Order: Edit first, Delete last.

## Rules

- Always include a confirmation for delete row action
- Row action links must use the `UrlGenerator` — not hardcoded URLs
