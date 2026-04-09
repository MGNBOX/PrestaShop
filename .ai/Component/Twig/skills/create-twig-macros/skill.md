---
name: create-twig-macros
brick: "—"
component: Twig
step: 9
needs: []
produces: "Domain-specific Twig macros for reusable template fragments"
conditional: "only if index or form templates have reusable fragments worth extracting"
---

# create-twig-macros

## Description
Documents when and how to create Twig macros for domain-specific template fragments that appear in multiple places. Prefer inline templates for single-use fragments — only extract to macros when used 3+ times.

## Context
- **Brick:** — — Step 9
- **Reads from:** T1, T2 (templates that would include the macros)
- **Writes to:** T1, T2 (macro calls replace duplicated fragments)
- **Artifact:** `src/PrestaShopBundle/Resources/views/Admin/{Section}/{Domain}/macros.html.twig`
- **PS example:** Check existing macro files under `src/PrestaShopBundle/Resources/views/Admin/`

## Instructions

1. Create `macros.html.twig` with `{% macro name(args) %}...{% endmacro %}`.
2. Import in templates: `{% import '@PrestaShop/Admin/{Section}/{Domain}/macros.html.twig' as macros %}`.
3. Use: `{{ macros.name(arg) }}`.

## Rules

- Create macros only for fragments used in 3+ places
- Macros must not access global Twig variables — pass all data as arguments
