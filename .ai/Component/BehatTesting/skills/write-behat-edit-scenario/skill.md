---
name: write-behat-edit-scenario
brick: B4
component: BehatTesting
step: 3
needs: [B3, D3]
produces: "Edit{Domain} scenario in feature file + step definitions"
conditional: false
---

# write-behat-edit-scenario

## Description
Write the Gherkin scenario and step definitions for the edit operation. Requires an entity created in B3 (via sharedStorage reference).

## Context
- **Brick:** B4 — Step 3
- **Reads from:** B3 (entity reference in sharedStorage), D3 (Edit{Domain}Command)
- **Writes to:** B5 (delete scenario references same entity)
- **Artifact:** Feature file + context class (edits)
- **PS example:** See carrier feature file edit scenarios

## Instructions

1. `Given {domain} "carrier_1" exists` (uses ref from B3).
2. `When I edit {domain} "carrier_1" with name "Updated Name"`.
3. `Then {domain} "carrier_1" should have name "Updated Name"`.
4. Implement partial-update step: build Edit{Domain}Command with only the fields being edited.
5. Verify unchanged fields remain unchanged.

## Rules

- Do not recreate an entity in edit scenario — use the ref created in B3
- Test that only specified fields change (partial update validation)
