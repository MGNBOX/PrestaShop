---
name: implement-bulk-delete-handler
brick: P7
component: CQRS
step: 2
needs: [D11, P1]
produces: "BulkDelete{Domain}sHandler.php"
conditional: "only if D11 was created"
---

# implement-bulk-delete-handler

## Description
Loop over the list of IDs and call the single-delete repository method for each. Collect errors and throw a bulk exception if any deletions fail.

## Context
- **Brick:** P7 — Step 2
- **Reads from:** D11 (bulk delete command structure), P1 (repository to call)
- **Writes to:** H1 (controller calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/BulkDelete{Domain}sHandler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/BulkDeleteCarriersHandler.php`

## Instructions

1. Iterate `$command->getIds()`.
2. For each ID, call `$this->repository->delete(...)` in a try/catch.
3. Collect any exceptions; continue with remaining IDs.
4. If any failures, throw a bulk exception listing the failed IDs.

## Rules

- Always continue after individual deletion failure — do not abort mid-batch
- Report ALL failed IDs in the bulk exception, not just the first one
- Reuse the same single-delete logic as the single-delete handler via the repository
