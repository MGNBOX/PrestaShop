---
name: implement-edit-command-handler
brick: P3
component: CQRS
step: 2
needs: [D3, D9, P1]
produces: "Edit{Domain}Handler.php — partial-update handler"
conditional: false
---

# implement-edit-command-handler

## Description
Implement the edit handler using the partial-update pattern. Only fields explicitly set on the command (non-null) are updated in the database.

## Context
- **Brick:** P3 — Step 2
- **Reads from:** D3 (edit command structure), D9 (interface to implement), P1 (repository to call)
- **Writes to:** F3 (form data handler dispatches this), H1 (controller calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/Edit{Domain}Handler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/EditCarrierHandler.php`

## Instructions

1. Load the existing entity via `$this->repository->get{Domain}($command->getId())`.
2. For each field: `if ($command->getName() !== null) { $entity->name = $command->getName(); }`.
3. Apply only non-null fields — never overwrite with null.
4. Call `$this->repository->update($entity)`.
5. Handle sub-resource commands separately (dispatched independently, not composed here).

## Rules

- Check null before every field update — this IS the partial-update pattern
- Never merge sub-resource updates into this handler
- Load then update — never blind update without loading first
