---
name: create-twig-form-block
brick: T4
component: Twig
step: 9
needs: [T2, F1]
produces: "Twig blocks overriding specific form field rendering (e.g., image preview, custom widget)"
conditional: "only if specific form fields need custom rendering"
---

# create-twig-form-block

## Description
Create Twig block overrides for form fields that need custom rendering beyond Symfony's default form_widget. Common cases: displaying an existing image preview next to the upload field, or a custom compound widget.

## Context
- **Brick:** T4 — Step 9
- **Reads from:** T2 (form template where overrides are applied), F1 (field IDs to override)
- **Writes to:** T2 (applied via form_theme in the form template)
- **Artifact:** Form template overrides within `form.html.twig` or a dedicated `_form_widgets.html.twig`
- **PS example:** Check existing form widget overrides under `src/PrestaShopBundle/Resources/views/Admin/`

## Instructions

1. In form template, override specific field widget: `{% form_theme form 'Admin/{Section}/{Domain}/_form_widgets.html.twig' %}`.
2. In the theme file, override: `{% block _{field_id}_widget %}` with custom HTML.
3. For image preview: show existing image from DataProvider with a "Remove" checkbox below the FileType input.

## Rules

- Form theme overrides are scoped to this form only via `form_theme` — no global side effects
- Keep overrides minimal — Symfony's default rendering handles most cases
