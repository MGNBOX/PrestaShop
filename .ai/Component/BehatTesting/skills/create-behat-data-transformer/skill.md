---
name: create-behat-data-transformer
brick: "—"
component: BehatTesting
step: 3
needs: [B2]
produces: "TableNode-to-command data transformer for complex Gherkin tables"
conditional: false
---

# create-behat-data-transformer

## Description
Create the data transformer that converts Gherkin `TableNode` or `PyStringNode` data into the typed parameters needed by CQRS commands. Handles type coercion, reference resolution, and multilingual values.

## Context
- **Brick:** — — Step 3
- **Reads from:** B2 (context class to extend)
- **Writes to:** B3–B6 (all create/edit steps use this transformer)
- **Artifact:** Helper method in `{Domain}FeatureContext.php` or a `{Domain}DataTransformer.php` utility
- **PS example:** Check existing context classes for `parseCreateCarrierData()` pattern

## Instructions

1. Create `private function parseCreateData(TableNode $table): array` in the context class.
2. Map Gherkin column names to command parameter names.
3. Coerce types: `"true"/"false"` → bool, `"1"/"2"` → int, `"[en:foo,fr:bar]"` → `[1 => 'foo', 2 => 'bar']` (lang array).
4. Resolve entity references from sharedStorage: `"carrier_1"` → int ID.
5. Return typed array for command constructor.

## Rules

- Type coercion must be explicit — never rely on PHP loose comparison
- Reference resolution must throw a clear error for unknown references
- Multilingual parsing format must be consistent across all features
