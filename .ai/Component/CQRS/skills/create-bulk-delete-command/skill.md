---
name: create-bulk-delete-command
brick: D11
component: CQRS
step: 11
needs: [D1]
produces: "BulkDelete{Domain}sCommand.php"
conditional: "only if the grid has a bulk delete action"
---

# create-bulk-delete-command

## Description
Command carrying a list of entity IDs for bulk deletion. Dispatched when the user selects multiple rows and clicks "Delete selected".

## Context
- **Brick:** D11 — Step 11
- **Reads from:** D1 ({Domain}Id), A3 manifest Section 4 (grid actions)
- **Writes to:** D9 (BulkDelete{Domain}sHandlerInterface), P4 bulk variant
- **Artifact:** `src/Core/Domain/{Domain}/Command/BulkDelete{Domain}sCommand.php`
- **PS example:** `src/Core/Domain/Carrier/Command/BulkDeleteCarriersCommand.php`

## Instructions

1. Check A3 manifest Section 4 (grid actions) — confirm bulk delete is required.
2. Constructor takes `array $ids` of `{Domain}Id` (or `array $ids` of int, depending on PS convention — check Carrier).
3. Add `getIds(): array` getter.
4. If the domain convention uses raw int IDs in bulk commands, follow that pattern consistently.
5. Class must be `final` with `declare(strict_types=1)`.

## Rules

- Skip this skill entirely if the grid has no bulk delete action (check A3 manifest)
- Be consistent with the domain convention on whether IDs are typed or raw int
- Class is final with declare(strict_types=1)
