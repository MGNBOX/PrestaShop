---
name: implement-get-query-handler
brick: P5
component: CQRS
step: 2
needs: [D5, D10, P1]
produces: "Get{Domain}ForEditingHandler.php — returns populated edit DTO"
conditional: false
---

# implement-get-query-handler

## Description
Implement the query handler that loads the entity and maps it to the edit DTO or array returned to the form data provider.

## Context
- **Brick:** P5 — Step 2
- **Reads from:** D5 (get query structure), D10 (interface to implement), P1 (repository to call)
- **Writes to:** F2 (form data provider reads this handler's output)
- **Artifact:** `src/Adapter/{Domain}/QueryHandler/Get{Domain}ForEditingHandler.php`
- **PS example:** `src/Adapter/Carrier/QueryHandler/GetCarrierForEditingHandler.php`

## Instructions

1. Inject `{Domain}Repository` and any needed lang/shop context.
2. Load entity: `$entity = $this->repository->get{Domain}($query->getId())`.
3. Map all fields to the return DTO or array: scalar fields, multilingual fields (array keyed by langId), related IDs.
4. Return typed DTO or associative array, per domain convention.

## Rules

- No write side effects in query handlers
- Map ALL editable fields — missing fields cause form to show empty on edit
