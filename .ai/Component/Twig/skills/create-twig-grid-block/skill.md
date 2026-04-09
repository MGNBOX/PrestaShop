---
name: create-twig-grid-block
brick: T3
component: Twig
step: 9
needs: [T1, G1]
produces: "Twig block overriding grid panel rendering for domain-specific customizations"
conditional: "only if the grid needs domain-specific column rendering beyond defaults"
---

# create-twig-grid-block

## Description
Create a Twig block that overrides specific grid column renderers when the default PrestaShop column rendering is insufficient for the domain. Most grids do not need this — only add it if a column requires custom HTML.

## Context
- **Brick:** T3 — Step 9
- **Reads from:** T1 (included from index template), G1 (column IDs to override)
- **Writes to:** T1 (block included in index template)
- **Artifact:** `src/PrestaShopBundle/Resources/views/Admin/{Section}/{Domain}/Blocks/grid_block.html.twig`
- **PS example:** Check existing domain grid block overrides under `src/PrestaShopBundle/Resources/views/Admin/`

## Instructions

1. Extend or use `@PrestaShop/Admin/Common/Grid/grid_panel.html.twig`.
2. Override specific column block: `{% block column_{column_id}_content %}`.
3. Render custom HTML for that column using the row data.
4. For image columns: `<img src="{{ imageUrl }}" alt="">`.

## Rules

- Only override columns that need custom rendering — leave defaults alone
- Never hardcode image paths — use the PS link helper or Twig extension
