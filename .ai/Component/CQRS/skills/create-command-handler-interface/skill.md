---
name: create-command-handler-interface
brick: D9
component: CQRS
step: 9
needs: [D2, D3, D4, D11, D12, D13]
produces: "Handler interfaces in src/Core/Domain/{Domain}/CommandHandler/ — contracts for all write operations"
conditional: false
---

# create-command-handler-interface

## Description
Create the handler interface for each command. These interfaces live in Core and define the contract — the concrete implementations in Adapter are registered to satisfy these interfaces.

## Context
- **Brick:** D9 — Step 9
- **Reads from:** D2 (AddCommand), D3 (EditCommand), D4 (DeleteCommand), D11–D14 as applicable
- **Writes to:** P2–P10 (concrete handler classes implement these interfaces)
- **Artifact:** `src/Core/Domain/{Domain}/CommandHandler/Add{Domain}HandlerInterface.php` etc.
- **PS example:** `src/Core/Domain/Carrier/CommandHandler/`

## Instructions

1. For each command (Add, Edit, Delete, BulkDelete, BulkToggleStatus, ToggleStatus), create a corresponding `{Action}{Domain}HandlerInterface.php`.
2. Each interface extends `CommandHandlerInterface` (from PrestaShop Core).
3. Single method: `public function handle({Action}{Domain}Command $command): void` (or return type if handler produces a result — e.g., Add returns {Domain}Id).
4. Interfaces live in `src/Core/Domain/{Domain}/CommandHandler/`.
5. Add handler interfaces only for commands that actually exist.

## Rules

- `Add{Domain}Handler` typically returns `{Domain}Id` (the new entity's ID)
- Edit, Delete, Bulk handlers return `void`
- One interface per command — never combine multiple commands in one interface
