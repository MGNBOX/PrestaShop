---
name: create-grid-action-types
brick: G4
component: Grid
step: 4
needs: [G1, H2]
produces: "Row actions and bulk actions wired to Symfony routes in the Grid Definition"
conditional: false
---

# create-grid-action-types

## Description
Wire the grid row actions (Edit, Delete) and bulk actions (Enable, Disable, Delete) to the Symfony routes created in H2. This is done within the Grid Definition Factory (G1).

## Context
- **Brick:** G4 — Step 4
- **Reads from:** G1 (factory to edit), H2 (route names to reference)
- **Writes to:** H1 (controller handles these action routes)
- **Artifact:** Grid Definition Factory (edit G1 output) — adds action routing
- **PS example:** `src/Core/Grid/Definition/Factory/CarrierGridDefinitionFactory.php` (row/bulk action definitions)

## Instructions

1. In `getRowActions()`, add an `EditAction` linking to `admin_{domain}s_edit` route with `{id}` parameter.
2. Add a `DeleteAction` with confirmation modal linking to `admin_{domain}s_delete` route.
3. In `getBulkActions()`, add `SubmitBulkAction` for enable: submits form to `admin_{domain}s_bulk_enable_status` route.
4. Add submit bulk action for disable targeting `admin_{domain}s_bulk_disable_status`.
5. Add submit bulk action for delete: `admin_{domain}s_bulk_delete`.
6. Route names must match the routes defined in H2 exactly.

## Rules

- Route names in actions must match H2 exactly — typos cause silent 404s
- Delete row action must use a confirmation modal (use `LinkRowAction` with `confirm_message` option)
- Bulk actions use form submission — not GET requests
