---
name: create-controller-bulk-actions
brick: "—"
component: Controller
step: 5
needs: [H1, D11, D12, D13]
produces: "bulkDeleteAction(), bulkEnableStatusAction(), bulkDisableStatusAction() methods"
conditional: "only if bulk commands exist (D11, D12, D13)"
---

# create-controller-bulk-actions

## Description
Documents the three bulk action controller methods. Each reads the selected IDs from the submitted form, dispatches the appropriate bulk command, and redirects with a result flash.

## Context
- **Brick:** — — Step 5
- **Reads from:** H1 (controller skeleton), D11 (BulkDelete command), D12 (BulkEnable command), D13 (BulkDisable command)
- **Writes to:** T1 (redirects back to index listing)
- **Artifact:** `{Domain}Controller.php` (edit H1 output)
- **PS example:** `src/PrestaShopBundle/Controller/Admin/Shipping/CarrierController.php`

## Instructions

1. All bulk actions receive `Request $request`.
2. Extract selected IDs: `$ids = $request->request->get('{domain}BulkAction', [])`.
3. Convert to `{Domain}Id` array: map each int to `new {Domain}Id($id)`.
4. Dispatch bulk command.
5. Catch bulk exceptions: add flash listing failed IDs.
6. Redirect to index.

## Rules

- Empty selection should return early with an info flash ("no items selected")
- Bulk delete must redirect to a confirmation page or use JS confirm in the grid
