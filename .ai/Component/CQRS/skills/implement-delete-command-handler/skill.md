---
name: implement-delete-command-handler
brick: P4
component: CQRS
step: 2
needs: [D4, D9, P1]
produces: "Delete{Domain}Handler.php"
conditional: false
---

# implement-delete-command-handler

## Description
Implement the delete handler. Verifies the entity exists, checks business constraints (e.g., cannot delete if referenced by active orders), then calls repository delete.

## Context
- **Brick:** P4 — Step 2
- **Reads from:** D4 (delete command structure), D9 (interface to implement), P1 (repository to call)
- **Writes to:** H1 (controller calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/Delete{Domain}Handler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/DeleteCarrierHandler.php`

## Instructions

1. Load entity to verify existence (throws NotFoundException if not found).
2. Check business constraints (from A1 audit): if entity is referenced, throw `Cannot{Delete}{Domain}Exception`.
3. Call `$this->repository->delete($command->getId(), $shopConstraint)`.

## Rules

- Always verify existence before deletion — never delete blindly
- Business constraint checks come before any deletion attempt
- Use multistore-aware delete via getShopIdsByConstraint
