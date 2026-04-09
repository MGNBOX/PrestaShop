---
name: write-behat-filter-scenario
brick: "—"
component: BehatTesting
step: 3
needs: [B2, G2]
produces: "Filter and search scenarios in the feature file"
conditional: false
---

# write-behat-filter-scenario

## Description
Write Behat scenarios that verify the grid filtering and search functionality. Creates test data and verifies filtered results.

## Context
- **Brick:** — — Step 3
- **Reads from:** B2 (context class), G2 (grid filter definitions)
- **Writes to:** — (extends feature file coverage)
- **Artifact:** Feature file + context class (edits)
- **PS example:** See existing filter scenarios in carrier or product feature files

## Instructions

1. Create multiple entities with different names, statuses, etc.
2. Apply a filter: `When I filter {domain}s by name "Test"`.
3. Assert only matching entities appear: `Then I should see {domain} "carrier_1" in the list`.
4. Assert non-matching entities are absent.
5. Reset filter and verify all entities appear again.

## Rules

- Create predictable test data with distinct values per filter field
- Test at least 2 different filter types
- Always reset filters between scenarios to prevent state leakage
