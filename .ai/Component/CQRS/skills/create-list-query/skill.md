---
name: create-list-query
brick: D6
component: CQRS
step: 6
needs: [A3]
produces: "Get{Domain}sForListing.php or SearchCriteria-based query — grid data source query"
conditional: false
---

# create-list-query

## Description
Create the query used by the grid to retrieve the paginated, filtered, sorted list of entities. In most PS domains this is handled by the grid query builder (G2) rather than an explicit CQRS query; assess which pattern the domain uses.

## Context
- **Brick:** D6 — Step 6
- **Reads from:** A3 manifest (grid columns, filter definitions)
- **Writes to:** G2 (grid query builder), P6 (if explicit query handler exists)
- **Artifact:** `src/Core/Domain/{Domain}/Query/Get{Domain}sForListing.php` or via SearchCriteria
- **PS example:** `src/Core/Domain/Carrier/Query/` or grid QueryBuilder pattern (G2)

## Instructions

1. Check if the domain uses an explicit `Get{Domain}sForListing` query or delegates to `SearchCriteria` + grid query builder.
2. For SearchCriteria approach: no explicit query class needed — G2 handles it.
3. For explicit query: create `Get{Domain}sForListing.php` with `SearchCriteria $searchCriteria` parameter.
4. Add getter for search criteria.
5. Create corresponding handler interface in `QueryHandler/` folder.

## Rules

- Most PS grids use the SearchCriteria + QueryBuilder pattern — do not create an unnecessary query class
- If uncertain, examine `src/Core/Domain/Carrier/` for the established pattern
