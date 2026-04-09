---
name: create-controller-delete-action
brick: "—"
component: Controller
step: 5
needs: [H1, D4]
produces: "deleteAction() — dispatches Delete command with CSRF protection"
conditional: false
---

# create-controller-delete-action

## Description
Documents the delete action. Validates CSRF token, dispatches Delete command, handles not-found and constraint exceptions, and redirects with flash.

## Context
- **Brick:** — — Step 5
- **Reads from:** H1 (controller skeleton), D4 (Delete{Domain}Command)
- **Writes to:** T1 (redirects back to index listing)
- **Artifact:** `{Domain}Controller.php` (edit H1 output)
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. Validate CSRF token from request.
2. Dispatch `Delete{Domain}Command`.
3. Catch `{Domain}NotFoundException` and `Cannot{Action}{Domain}Exception`.
4. Add appropriate flash (success, warning, or error).
5. Redirect to index.

## Rules

- Always validate CSRF on destructive actions
- Distinguish NotFoundException (entity gone) from ConstraintException (cannot delete due to business rule)
