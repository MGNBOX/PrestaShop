---
name: create-query-result-dto
brick: —
component: CQRS
step: 1
needs: [D5, D10]
produces: "Editable{Domain}.php — typed DTO returned by Get{Domain}ForEditing query handler"
conditional: false
---

# create-query-result-dto

## Description
Create the typed DTO that the `Get{Domain}ForEditing` query handler returns. This replaces untyped arrays with a proper PHP object with named, typed getters.

## Context
- **Brick:** — — Step 1
- **Reads from:** D5 (get query fields), D10 (query handler interface)
- **Writes to:** P5 (get query handler returns this DTO), F2 (form data provider reads it)
- **Artifact:** `src/Core/Domain/{Domain}/QueryResult/Editable{Domain}.php`
- **PS example:** `src/Core/Domain/Carrier/QueryResult/EditableCarrier.php`

## Instructions

1. Create `src/Core/Domain/{Domain}/QueryResult/Editable{Domain}.php`.
2. Constructor parameters: all fields that the edit form needs to pre-fill.
3. Typed constructor parameters: int/string/bool scalars, `array` for multilingual fields, `?int` for nullable foreign keys.
4. Add a public getter for every field.
5. The class is a pure data object — no methods, no dependencies.

## Rules

- Immutable: no setters, all values set at construction
- Multilingual fields are `array` keyed by language ID
- No ObjectModel instances inside the DTO — only scalars and arrays
