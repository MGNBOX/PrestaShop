---
name: create-controller-edit-action
brick: "—"
component: Controller
step: 5
needs: [H1, F2, F3, D3, D5]
produces: "editAction() — GET loads entity into form, POST dispatches Edit command"
conditional: false
---

# create-controller-edit-action

## Description
Documents the edit action. GET loads the entity via Get query and pre-fills the form using the DataProvider; POST dispatches the Edit command via FormDataHandler.

## Context
- **Brick:** — — Step 5
- **Reads from:** H1 (controller skeleton), F2 (form data provider to pre-fill form), F3 (form data handler to dispatch D3), D3 (Edit command), D5 (Get{Domain}ForEditing query)
- **Writes to:** T2 (create/edit form template)
- **Artifact:** `{Domain}Controller.php` (edit H1 output)
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. GET: dispatch `Get{Domain}ForEditing` query → pass result to `{Domain}FormDataProvider->getData($id)` → pre-fill form.
2. POST: handle request, validate form, call FormDataHandler update method, redirect on success.
3. Catch `{Domain}NotFoundException` → add error flash, redirect to index.

## Rules

- Always catch NotFoundException before dispatching edit — entity may have been deleted concurrently
- FormDataProvider must transform the query result into form-ready data, not the controller
