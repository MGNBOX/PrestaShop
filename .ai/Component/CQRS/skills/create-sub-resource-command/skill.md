---
name: create-sub-resource-command
brick: D14
component: CQRS
step: 14
needs: [D1, A3]
produces: "Set{Domain}{SubResource}sCommand.php — atomic replace command for a sub-resource collection"
conditional: "only if domain has sub-resources (has-many relations)"
---

# create-sub-resource-command

## Description
Command for replacing the full collection of a sub-resource (e.g., carrier zones, carrier ranges). Always uses atomic replace — delete all then insert all — never partial merge.

## Context
- **Brick:** D14 — Step 14
- **Reads from:** D1 ({Domain}Id), A3 manifest Section 6 (sub-resources)
- **Writes to:** D9 (Set{Domain}{SubResource}sHandlerInterface), P10 (delete-all + insert-all handler)
- **Artifact:** `src/Core/Domain/{Domain}/Command/{SubResource}/Set{Domain}{SubResource}sCommand.php`
- **PS example:** `src/Core/Domain/Carrier/Command/SetCarrierShippingZonesCommand.php` or similar

## Instructions

1. Check A3 manifest Section 6 (sub-resources) — one command per sub-resource type.
2. Constructor takes `{Domain}Id` and the full replacement collection (array or typed collection).
3. Handler (P10) deletes all existing sub-resources then inserts the new set.
4. Never use partial update for sub-resources — always full replace.
5. Add `getId(): {Domain}Id` and `get{SubResource}s(): array` getters.
6. If multiple sub-resource types exist, create one command class per sub-resource type.

## Rules

- One command per sub-resource type
- The collection replaces the entire set — partial update is not supported
- If the user sends an empty array, all sub-resources are deleted
- Skip this skill entirely if the domain has no has-many sub-resources (check A3 manifest)
