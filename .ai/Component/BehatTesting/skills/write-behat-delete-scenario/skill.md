---
name: write-behat-delete-scenario
brick: B5
component: BehatTesting
step: 3
needs: [B3, D4]
produces: "Delete{Domain} scenario in feature file + step definitions"
conditional: false
---

# write-behat-delete-scenario

## Description
Write the delete scenario and its step definitions. Tests both successful deletion and the not-found case after deletion.

## Context
- **Brick:** B5 — Step 3
- **Reads from:** B3 (entity reference in sharedStorage), D4 (Delete{Domain}Command)
- **Writes to:** — (terminal step for basic CRUD coverage)
- **Artifact:** Feature file + context class (edits)
- **PS example:** See carrier feature file delete scenarios

## Instructions

1. `When I delete {domain} "carrier_1"`.
2. `Then {domain} "carrier_1" should not exist`.
3. In step implementation: dispatch DeleteCommand, then verify Get query throws NotFoundException.
4. Add error scenario: `When I delete non-existent {domain} "ghost_ref" Then I should get {Domain}NotFoundException`.

## Rules

- After deletion, always verify via query that the entity no longer exists
- Use typed NotFoundException from D7 — not a generic assertion on null
