---
name: implement-bulk-status-handler
brick: P8
component: CQRS
step: 2
needs: [D12, P1]
produces: "BulkToggle{Domain}ActiveStatusHandler.php"
conditional: "only if D12 was created"
---

# implement-bulk-status-handler

## Description
Loop over IDs and set `active` status to the target value for each.

## Context
- **Brick:** P8 — Step 2
- **Reads from:** D12 (bulk status command structure), P1 (repository to call)
- **Writes to:** H1 (controller calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/BulkToggle{Domain}ActiveStatusHandler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/BulkToggleCarriersStatusHandler.php`

## Instructions

1. Iterate IDs, load entity, set `active = $command->getExpectedStatus()`, update.
2. Collect and report failures.

## Rules

- Always continue after individual status-update failure — do not abort mid-batch
- Report ALL failed IDs in the bulk exception
- Use the target status from the command — do not flip the current value
