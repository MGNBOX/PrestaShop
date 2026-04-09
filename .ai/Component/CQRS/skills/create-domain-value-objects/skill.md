---
name: create-domain-value-objects
brick: D8
component: CQRS
step: 8
needs: [A3, A2]
produces: "Additional ValueObject classes for complex field types (e.g., CarrierRanges, TrackingUrl, Grade)"
conditional: false
---

# create-domain-value-objects

## Description
Create ValueObject classes for domain fields that have validation rules beyond primitive type checking. Each ValueObject wraps a primitive and enforces its invariants at construction.

## Context
- **Brick:** D8 — Step 8
- **Reads from:** A2 (validation rules per field), A3 manifest (field list)
- **Writes to:** D2, D3 (commands use these ValueObjects), P1–P10 (handlers unwrap them for persistence)
- **Artifact:** `src/Core/Domain/{Domain}/ValueObject/` (multiple files)
- **PS example:** `src/Core/Domain/Carrier/ValueObject/` (e.g., `ShippingMethod.php`, `CarrierEditableFields.php`)

## Instructions

1. From A2 audit, identify fields with `Validate::isXxx` rules or business constraints (not just `Validate::isString`).
2. For each such field, create a ValueObject: `final class {FieldName} { private {type} $value; }`.
3. Constructor validates the value; throws `{Domain}Exception` on violation.
4. Add `getValue()` and any domain-specific methods (e.g., `toString()`, `equals()`).
5. For enum-like fields (e.g., `ShippingMethod` with PRICE/WEIGHT values), add constants and validate against them.
6. For URL fields (e.g., tracking URL), validate format with a regex or `filter_var`.

## Rules

- ValueObjects are immutable — no setters
- Use only PHP primitives inside; no Symfony/Doctrine types
- Not every field needs a ValueObject — only those with non-trivial validation or domain meaning
