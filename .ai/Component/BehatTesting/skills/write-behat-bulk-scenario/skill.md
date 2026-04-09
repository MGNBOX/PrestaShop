---
name: write-behat-bulk-scenario
brick: B6
component: BehatTesting
step: 3
needs: [B3, D11, D12, D13]
produces: "Bulk action scenarios (bulk delete, bulk enable/disable, toggle)"
conditional: "only if bulk commands were created (D11, D12, D13)"
---

# write-behat-bulk-scenario

## Description
Write scenarios for bulk operations (bulk delete, bulk enable, bulk disable) and single-row status toggle. Requires multiple entities created in B3.

## Context
- **Brick:** B6 — Step 3
- **Reads from:** B3 (entity refs in sharedStorage), D11/D12/D13 (bulk commands)
- **Writes to:** — (terminal step for bulk coverage)
- **Artifact:** Feature file + context class (edits)
- **PS example:** See carrier feature file bulk action scenarios

## Instructions

1. Create 3 entities: "carrier_1", "carrier_2", "carrier_3".
2. Bulk delete: `When I bulk delete carriers "carrier_1,carrier_2"` → `Then they should not exist`.
3. Bulk enable: `When I bulk enable carriers "carrier_1,carrier_2"` → `Then they should be active`.
4. Toggle single: `When I toggle status of carrier "carrier_3"` → `Then "carrier_3" should be inactive`.

## Rules

- Skip this skill entirely if D11, D12, and D13 were not produced (no bulk commands exist)
- Parse comma-separated reference lists in step definitions — resolve each from sharedStorage
- Assert the toggled state is the inverse of the prior state — do not assume a starting state
