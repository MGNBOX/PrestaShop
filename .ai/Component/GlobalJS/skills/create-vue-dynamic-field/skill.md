---
name: create-vue-dynamic-field
brick: JS4
component: GlobalJS
step: 8
needs: [JS2, F1]
produces: "Vue SFC for a single dynamic field or field group (e.g., price ranges, zone multiselect)"
conditional: "only for fields with dynamic rows, conditional visibility, or live calculation"
---

# create-vue-dynamic-field

## Description
Create a Vue SFC for a single complex field or field group that requires reactivity — such as a dynamic price range table where rows can be added/removed, or a conditional field that appears based on another field's value.

## Context
- **Brick:** JS4 — Step 8
- **Reads from:** JS2 or JS3 (receives initial data as prop)
- **Writes to:** JS2 (emits updated value on change)
- **Artifact:** `admin-dev/themes/new-theme/js/pages/{domain}/{FieldName}Field.vue`
- **PS example:** Dynamic price range or zone multiselect components under `admin-dev/themes/new-theme/js/pages/`

## Instructions

1. Props: `modelValue` of the appropriate type (array for multi-row, string/number for single).
2. Emit: `update:modelValue` for v-model compatibility.
3. For multi-row (e.g., ranges): render a `<table>` with a row per item, "Add row" button, and "Remove" per row.
4. For conditional visibility: `v-if="otherField === 'value'"`.
5. Sync final value to hidden input for Symfony.

## Rules

- Use v-model pattern (modelValue prop + update:modelValue emit) for composability
- Rows added at runtime must be immediately reflected in the hidden input's value
