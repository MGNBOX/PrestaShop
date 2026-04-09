---
name: implement-sub-resource-handler
brick: P10
component: CQRS
step: 2
needs: [D14, P1]
produces: "Set{Domain}{SubResource}sHandler.php — atomic replace handler for sub-resources"
conditional: "only if D14 was created"
---

# implement-sub-resource-handler

## Description
Implement the atomic replace pattern: delete all existing sub-resource rows for the entity, then insert the new collection from the command. Never do partial updates.

## Context
- **Brick:** P10 — Step 2
- **Reads from:** D14 (sub-resource command structure), P1 (repository set{SubResource}s method)
- **Writes to:** H1 (controller dispatches alongside edit command)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/{SubResource}/Set{Domain}{SubResource}sHandler.php`
- **PS example:** Any carrier zone/range handler

## Instructions

1. Begin a DB transaction.
2. Delete all existing sub-resource rows: `DELETE FROM ps_{sub_resource} WHERE id_{domain} = ?`.
3. Insert new rows from `$command->getItems()`.
4. Commit. On failure, rollback and throw domain exception.

## Rules

- ALWAYS use a transaction for delete+insert
- ALWAYS delete all first, then insert all — never partial merge
- Empty array = delete all (valid use case)
