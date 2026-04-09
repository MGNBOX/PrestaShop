---
name: create-symfony-admin-controller
brick: H1
component: Controller
step: 5
needs: [D9, D10, G1, F2, F3]
produces: "{Domain}Controller.php — Symfony admin controller with all CRUD actions"
conditional: false
---

# create-symfony-admin-controller

## Description
Create the Symfony admin controller that wires all CRUD actions to the command/query bus. The controller dispatches commands and queries — it contains no business logic.

## Context
- **Brick:** H1 — Step 5
- **Reads from:** D9/D10 (command/query interfaces to dispatch), G1 (grid definition for index), F2/F3 (form data provider/handler for edit)
- **Writes to:** T1/T2 (renders templates), H2 (routes point to these actions)
- **Artifact:** `src/PrestaShopBundle/Controller/Admin/{Section}/{Domain}Controller.php`
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. Create controller extending `FrameworkBundleAdminController`.
2. Declare actions: `indexAction`, `createAction`, `editAction`, `deleteAction`.
3. `indexAction`: build grid with `$this->get('prestashop.core.grid.presenter.grid_presenter')`, render `index.html.twig`.
4. `createAction` GET: build empty form via `{Domain}FormDataProvider`, render form template.
5. `createAction` POST: build form, submit, call `{Domain}FormDataHandler->create($form)`, redirect to index with flash.
6. `editAction` GET: load via `Get{Domain}ForEditing` query, populate form, render.
7. `editAction` POST: submit form, call handler `->update($form)`, redirect.
8. `deleteAction`: dispatch `Delete{Domain}Command`, redirect with flash.
9. Add bulk action methods: `bulkDeleteAction`, `bulkEnableStatusAction`, `bulkDisableStatusAction`.
10. Catch typed domain exceptions (D7) and display user-friendly flash messages.

## Rules

- ZERO business logic in controllers — delegate everything to handlers via bus
- Never instantiate commands directly with `new` — use the form data handler (F3)
- All exceptions from handlers must be caught and turned into flash messages
- Toggle status action must return JSON for AJAX calls
