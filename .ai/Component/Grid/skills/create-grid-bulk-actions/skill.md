---
name: create-grid-bulk-actions
brick: —
component: Grid
step: 4
needs: [G1, H2]
produces: "BulkActionCollection with enable/disable/delete wired to correct routes"
conditional: false
---

# create-grid-bulk-actions

## Description
Documents the PrestaShop bulk action types and the correct way to wire them to form submission routes. Bulk actions submit a form containing the selected row IDs.

## Context
- **Brick:** — — Step 4
- **Reads from:** G1 (factory to edit), H2 (route names to reference)
- **Writes to:** G1 (bulk actions are defined inside the Grid Definition Factory)
- **Artifact:** Grid Definition Factory `getBulkActions()` method
- **PS example:** `src/Core/Grid/Definition/Factory/CarrierGridDefinitionFactory.php`

## Instructions

1. `SubmitBulkAction`: submits the grid form to a controller action. Use for enable/disable/delete.
2. Configure: `->setName('bulk_enable')->setOptions(['submit_route' => 'admin_{domain}s_bulk_enable_status'])`.
3. The form submission sends `{domain}BulkAction[]` array of selected IDs to the route.
4. Confirmation modal for bulk delete: add `confirm_bulk_action: true` to options.

## Rules

- Bulk delete must have a confirmation dialog
- Bulk action route names must match H2 exactly
