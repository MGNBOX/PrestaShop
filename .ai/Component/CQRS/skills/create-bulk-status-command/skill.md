---
name: create-bulk-status-command
brick: D12
component: CQRS
step: 12
needs: [D1]
produces: "BulkToggle{Domain}ActiveStatusCommand.php"
conditional: "only if the grid has a bulk enable/disable action"
---

# create-bulk-status-command

## Description
Command for bulk enabling or disabling multiple entities. Takes a list of IDs and a boolean target status.

## Context
- **Brick:** D12 — Step 12
- **Reads from:** D1 ({Domain}Id), A3 manifest Section 4 (grid bulk actions)
- **Writes to:** D9 (BulkToggle{Domain}StatusHandlerInterface), P handler
- **Artifact:** `src/Core/Domain/{Domain}/Command/BulkToggle{Domain}ActiveStatusCommand.php`
- **PS example:** `src/Core/Domain/Carrier/Command/BulkToggleCarrierStatusCommand.php`

## Instructions

1. Check A3 manifest Section 4 — confirm bulk enable/disable actions are required.
2. Constructor takes `array $ids` (of {Domain}Id or int) and `bool $expectedStatus`.
3. Add typed getters: `getIds(): array` and `getExpectedStatus(): bool`.
4. Class must be `final` with `declare(strict_types=1)`.
5. Follow the same ID type convention (typed vs raw int) used in D11 for consistency.

## Rules

- Skip if the grid has no bulk enable/disable action (check A3 manifest)
- `$expectedStatus = true` means enable; `false` means disable
- Be consistent with D11 on whether IDs are typed {Domain}Id or raw int
