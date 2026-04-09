---
name: implement-list-query-handler
brick: P6
component: CQRS
step: 2
needs: [D6, D10]
produces: "Get{Domain}sForListingHandler.php or handled by grid QueryBuilder"
conditional: "only if explicit list query exists; most PS domains use SearchCriteria+QueryBuilder"
---

# implement-list-query-handler

## Description
Implement the list query handler if the domain uses explicit CQRS queries for listing. Most PS domains skip this and use the grid QueryBuilder pattern (G2) instead.

## Context
- **Brick:** P6 — Step 2
- **Reads from:** D6 (list query structure), D10 (interface to implement)
- **Writes to:** G2 (grid query builder may be used instead)
- **Artifact:** `src/Adapter/{Domain}/QueryHandler/Get{Domain}sForListingHandler.php`
- **PS example:** See G2 (grid query builder) — most listing is handled there

## Instructions

1. Check whether domain uses explicit query class or SearchCriteria+QueryBuilder (G2).
2. If explicit: implement handler that calls QueryBuilder with SearchCriteria.
3. Return paginated array of row arrays.

## Rules

- Most domains use the grid QueryBuilder (G2) instead of this handler — confirm before creating
- If using SearchCriteria, do not duplicate filtering logic already in the QueryBuilder
