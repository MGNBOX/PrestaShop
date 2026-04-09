---
name: register-grid-service
brick: G5
component: Grid
step: 4
needs: [G1, G2, G3]
produces: "DI YAML registrations for grid definition factory, query builder, and filters"
conditional: false
---

# register-grid-service

## Description
Register the grid components (definition factory, query builder, filters form type) in the Symfony DI container with the required tags and constructor arguments.

## Context
- **Brick:** G5 — Step 4
- **Reads from:** G1, G2, G3 (class names and constructor dependencies)
- **Writes to:** H1 (controller auto-wires grid presenter which depends on these services)
- **Artifact:** `src/PrestaShopBundle/Resources/config/services/{domain}.yml` or grid services file
- **PS example:** Check grid service registrations for Carrier in `src/PrestaShopBundle/Resources/config/services/`

## Instructions

1. Register `{Domain}GridDefinitionFactory` with tag `prestashop.core.grid.definition.factory` and attribute `grid_id: {domain}`.
2. Register `{Domain}QueryBuilder` with `autowire: true` and inject `DBAL connection` if needed.
3. Register `{Domain}GridFilters` form type with tag `form.type`.
4. Run `php bin/console debug:container | grep {domain}` to verify.

## Rules

- Grid definition factory tag `grid_id` must match the ID returned by `getId()` in G1
- All constructor dependencies must be explicitly wired or autowired
