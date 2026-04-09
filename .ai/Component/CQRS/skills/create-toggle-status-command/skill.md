---
name: create-toggle-status-command
brick: D13
component: CQRS
step: 13
needs: [D1]
produces: "Toggle{Domain}ActiveStatusCommand.php — single-row status toggle"
conditional: false
---

# create-toggle-status-command

## Description
Command for toggling the active status of a single entity, used by the grid toggle switch (AJAX).

## Context
- **Brick:** D13 — Step 13
- **Reads from:** D1 ({Domain}Id)
- **Writes to:** D9 (Toggle{Domain}StatusHandlerInterface), P handler (reads current status and flips)
- **Artifact:** `src/Core/Domain/{Domain}/Command/Toggle{Domain}ActiveStatusCommand.php`
- **PS example:** `src/Core/Domain/Carrier/Command/ToggleCarrierStatusCommand.php`

## Instructions

1. Constructor takes `{Domain}Id $id` and optionally `bool $expectedStatus`.
2. Handler reads current status and flips it, or sets to `$expectedStatus` if provided.
3. Add `getId(): {Domain}Id` getter.
4. Add `getExpectedStatus(): ?bool` getter if `$expectedStatus` is included.
5. Class must be `final` with `declare(strict_types=1)`.

## Rules

- Always use typed {Domain}Id — never raw int
- The optional `$expectedStatus` allows the front-end toggle to specify the desired state explicitly
- Handler is responsible for reading current state if `$expectedStatus` is null
