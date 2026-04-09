---
name: create-twig-index-template
brick: T1
component: Twig
step: 9
needs: [G1, H1, H2]
produces: "index.html.twig — back-office listing page template extending the PS admin layout"
conditional: false
---

# create-twig-index-template

## Description
Create the Twig template for the entity listing (grid) page. Extends the PS admin layout and renders the grid, header buttons, and filters form.

## Context
- **Brick:** T1 — Step 9
- **Reads from:** G1 (grid variable passed by H1 controller), H2 (create route for "Add new" button)
- **Writes to:** T3 (grid block embedded here)
- **Artifact:** `src/PrestaShopBundle/Resources/views/Admin/{Section}/{Domain}/index.html.twig`
- **PS example:** `src/PrestaShopBundle/Resources/views/Admin/Shipping/Carrier/index.html.twig`

## Instructions

1. `{% extends '@PrestaShop/Admin/layout.html.twig' %}`.
2. `{% block content_title %}` — page title using `trans()`.
3. `{% block page_header_toolbar %}` — "Add new {Domain}" button linking to `admin_{domain}s_create` route.
4. `{% block content %}` — render `{% include '@PrestaShop/Admin/Common/Grid/grid_panel.html.twig' with {grid: grid} %}`.
5. Pass the `grid` variable rendered by the grid presenter (passed from H1 controller).
6. Include the filters form inside the grid panel block.

## Rules

- Always extend `@PrestaShop/Admin/layout.html.twig` — never copy HTML structure
- Use `path('admin_{domain}s_create')` for the "Add" button — never hardcode URLs
- The `grid` variable must match what the H1 controller passes to `render()`
