---
name: create-value-object-collection
brick: —
component: CQRS
step: 1
needs: [D8]
produces: "Typed collection ValueObject for has-many relations (e.g., ShippingZoneCollection)"
conditional: "only for domains with sub-resource collections"
---

# create-value-object-collection

## Description
Create a typed collection ValueObject when a domain field is a list of items rather than a scalar. Validates the collection at construction and provides iteration.

## Context
- **Brick:** — — Step 1
- **Reads from:** D8 (sub-resource value objects and types)
- **Writes to:** D14 (sub-resource command holds this collection), P10 (handler uses it)
- **Artifact:** `src/Core/Domain/{Domain}/ValueObject/{SubResource}Collection.php`
- **PS example:** `src/Core/Domain/Carrier/ValueObject/` (zone/range value objects)

## Instructions

1. Create `final class {SubResource}Collection`.
2. Constructor takes `array $items` — validate each item type.
3. Implement `\Countable` and `\IteratorAggregate` for easy iteration.
4. Add `isEmpty(): bool` helper.

## Rules

- Collections are immutable once constructed
- Validate element types at construction — fail fast
