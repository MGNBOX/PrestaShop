---
name: implement-toggle-status-handler
brick: P9
component: CQRS
step: 2
needs: [D13, P1]
produces: "Toggle{Domain}ActiveStatusHandler.php — AJAX single-row toggle"
conditional: false
---

# implement-toggle-status-handler

## Description
Load the entity, flip its active status, and save. Used by the grid toggle switch via AJAX.

## Context
- **Brick:** P9 — Step 2
- **Reads from:** D13 (toggle status command structure), P1 (repository to call)
- **Writes to:** H1 (controller AJAX action calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/Toggle{Domain}ActiveStatusHandler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/ToggleCarrierStatusHandler.php`

## Instructions

1. Load entity by ID.
2. Flip: `$entity->active = !$entity->active`.
3. Call repository update.
4. Return void.

## Rules

- This handler flips the current value — use BulkToggle (P8) when a target value is needed
- Return void — the controller reads back state from the grid, not from this handler
