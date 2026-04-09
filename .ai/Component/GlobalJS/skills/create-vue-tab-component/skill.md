---
name: create-vue-tab-component
brick: JS3
component: GlobalJS
step: 8
needs: [JS2, F4]
produces: "Vue SFC for a dynamic form tab section"
conditional: "only for tabs with dynamic/interactive content"
---

# create-vue-tab-component

## Description
Create a Vue SFC for a form tab that has dynamic behavior (e.g., showing/hiding fields based on a toggle, dynamic table rows). Static tabs rendered entirely by Twig do not need Vue components.

## Context
- **Brick:** JS3 — Step 8
- **Reads from:** JS2 (receives props from form manager)
- **Writes to:** JS2 (emits updates back to form manager)
- **Artifact:** `admin-dev/themes/new-theme/js/pages/{domain}/{TabName}Tab.vue`
- **PS example:** Check carrier shipping tab or zones tab for Vue components

## Instructions

1. Define `defineProps<{ propName: Type }>()` for all received data.
2. Define `defineEmits<{ (e: 'update:propName', value: Type): void }>()` for updates.
3. Template: render the dynamic section (e.g., a dynamic table of ranges, a conditional field group).
4. On user input: `emit('update:propName', newValue)`.
5. Use `v-model` on child inputs where possible.

## Rules

- Tab component is only created if the tab has JS-driven dynamic behavior
- Static-only tabs are handled purely in Twig (T4) — no Vue component needed
