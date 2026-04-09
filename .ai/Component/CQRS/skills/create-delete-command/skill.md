---
name: create-delete-command
brick: D4
component: CQRS
step: 4
needs: [D1]
produces: "Delete{Domain}Command.php — single-entity delete intention"
conditional: false
---

# create-delete-command

## Description
Minimal command carrying only the entity ID to delete. No other data needed — the handler verifies existence before deletion.

## Context
- **Brick:** D4 — Step 4
- **Reads from:** D1 ({Domain}Id)
- **Writes to:** D9 (Delete{Domain}HandlerInterface), P4
- **Artifact:** `src/Core/Domain/{Domain}/Command/Delete{Domain}Command.php`
- **PS example:** `src/Core/Domain/Carrier/Command/DeleteCarrierCommand.php`

## Instructions

1. Create `Delete{Domain}Command.php` with a single constructor parameter: `{Domain}Id $id`.
2. Add `getId(): {Domain}Id` getter.
3. No other properties.

## Rules

- Never accept raw `int` — always use {Domain}Id
- No soft-delete logic here — that goes in the handler (P4)
- Class is final with declare(strict_types=1)
