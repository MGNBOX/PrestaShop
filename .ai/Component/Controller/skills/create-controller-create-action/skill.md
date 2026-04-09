---
name: create-controller-create-action
brick: "—"
component: Controller
step: 5
needs: [H1, F2, F3, D2]
produces: "createAction() — GET renders empty form, POST dispatches Add command"
conditional: false
---

# create-controller-create-action

## Description
Documents the create action implementation. GET builds an empty form; POST validates, dispatches Add command via form data handler, and redirects with a success flash.

## Context
- **Brick:** — — Step 5
- **Reads from:** H1 (controller skeleton), F2 (form data provider for empty form), F3 (form data handler to dispatch D2), D2 (Add command)
- **Writes to:** T2 (create/edit form template)
- **Artifact:** `{Domain}Controller.php` (edit H1 output)
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. GET: `$form = $this->createForm({Domain}Type::class)`. Render form template.
2. POST: `$form->handleRequest($request)`. If valid, call `$this->getCommandBus()->handle($formDataHandler->getData($form))`.
3. Catch domain exceptions and add error flash.
4. On success: add `$this->addFlash('success', '...')` and redirect to index.
5. On validation failure: re-render form with errors.

## Rules

- Use the form data handler (F3) to convert form data to command — never build command manually in controller
- Flash message keys must use PS translation domain `Admin.Notifications.Success`
