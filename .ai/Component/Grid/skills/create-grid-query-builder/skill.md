---
name: create-grid-query-builder
brick: G2
component: Grid
step: 4
needs: [A3, A2]
produces: "{Domain}QueryBuilder.php — SQL query builder for grid listing with filters and pagination"
conditional: false
---

# create-grid-query-builder

## Description
Create the Doctrine DBAL QueryBuilder that fetches grid rows with support for filtering, sorting, and pagination. The column aliases in this query must exactly match the column IDs in the Grid Definition (G1).

## Context
- **Brick:** G2 — Step 4
- **Reads from:** A2 (table name, column names), A3 (filter definitions, sortable columns)
- **Writes to:** G1 (column alias contract), G3 (filters applied here), H1 (grid presenter calls this)
- **Artifact:** `src/Adapter/Grid/Query/{Domain}QueryBuilder.php`
- **PS example:** `src/Adapter/Grid/Query/CarrierQueryBuilder.php`

## Instructions

1. Create `{Domain}QueryBuilder.php` implementing `DoctrineQueryBuilderInterface` or extending `AbstractDoctrineQueryBuilder`.
2. Implement `getSearchQueryBuilder(SearchCriteria $searchCriteria): QueryBuilder` — returns rows for the current page.
3. Implement `getCountQueryBuilder(SearchCriteria $searchCriteria): QueryBuilder` — returns total count.
4. Base query: `SELECT c.id_{domain} AS id_{domain}, c.name, c.active FROM ps_{domain} c`.
5. For each filter in SearchCriteria, add a WHERE clause: `if ($searchCriteria->getFilters()['name'] ?? null) { $qb->andWhere('c.name LIKE :name')->setParameter('name', '%'.filter.'%'); }`.
6. Apply sorting: map `$searchCriteria->getOrderBy()` to actual column expressions.
7. Apply pagination: `->setFirstResult($offset)->setMaxResults($limit)`.
8. For multilingual columns, add a LEFT JOIN to the lang table with the current language ID.

## Rules

- Column aliases in SELECT must exactly match column IDs in Grid Definition (G1)
- NEVER use raw string concatenation for filter values — always use parameterized queries
- Always alias the primary key as `id_{domain}` for row action routing
- The count query must NOT include LIMIT/OFFSET — it counts all matching rows
