---
name: create-form-error-handler
brick: —
component: Forms
step: 7
needs: [F1, H1, JS5]
produces: "Form validation error display and tab-level error indicators"
conditional: false
---

# create-form-error-handler

## Description
Documents the complete form error handling flow: server-side validation via constraints on form fields, rendering errors in the template, and JavaScript navigation to the first tab containing an error.

## Context
- **Brick:** — — Step 7
- **Reads from:** F1 (form type with validation constraints), H1 (controller re-renders on invalid), JS5 (JS tab error navigator)
- **Writes to:** Twig template (error blocks), JS tab switcher
- **Artifact:** Form type (validation constraints) + controller error handling + JS tab error navigator
- **PS example:** Check any multi-tab PS form submission with validation errors

## Instructions

1. Add Symfony validation constraints directly on form fields (NotBlank, Length, Regex).
2. In controller: if `!$form->isValid()`, re-render form — errors are displayed automatically by Twig.
3. In Twig template: use `{{ form_errors(form) }}` globally and `{{ form_errors(field) }}` per field.
4. JS (JS5): on DOMContentLoaded, scan for `is-invalid` CSS classes inside each tab pane; switch to the first tab with an error.
5. Add `aria-invalid="true"` to invalid fields for accessibility.

## Rules

- Server-side validation is the source of truth — JS validation is enhancement only
- Tab error navigation (JS5) must activate the FIRST tab with an error, not the last
