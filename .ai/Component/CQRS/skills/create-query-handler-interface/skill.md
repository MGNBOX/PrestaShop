---
name: create-query-handler-interface
brick: D10
component: CQRS
step: 10
needs: [D5, D6]
produces: "Handler interfaces in src/Core/Domain/{Domain}/QueryHandler/"
conditional: false
---

# create-query-handler-interface

## Description
Create the query handler interfaces for all read operations. These define what return types the handlers must provide.

## Context
- **Brick:** D10 — Step 10
- **Reads from:** D5, D6
- **Writes to:** P5, P6
- **Artifact:** `src/Core/Domain/{Domain}/QueryHandler/Get{Domain}ForEditingHandlerInterface.php` etc.
- **PS example:** `src/Core/Domain/Carrier/QueryHandler/`

## Instructions

1. Create `Get{Domain}ForEditingHandlerInterface.php` extending `QueryHandlerInterface`.
2. Method: `public function handle(Get{Domain}ForEditing $query): Editable{Domain}` (or array, per domain convention).
3. If list query exists, create corresponding interface.

## Rules

- Query handlers return data — never void
- Return types should be typed DTOs or arrays, not ObjectModel instances
