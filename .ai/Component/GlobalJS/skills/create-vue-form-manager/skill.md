---
name: create-vue-form-manager
brick: JS2
component: GlobalJS
step: 8
needs: [JS1, F1]
produces: "{Domain}FormManager.vue — top-level Vue component orchestrating the form"
conditional: false
---

# create-vue-form-manager

## Description
Create the root Vue SFC that manages the overall form state and coordinates between sub-components. Holds the reactive state shared across tabs and emits form data to hidden inputs for Symfony form submission.

## Context
- **Brick:** JS2 — Step 8
- **Reads from:** JS1 (mounted by entry point), F1 (form structure defines the state shape)
- **Writes to:** JS3/JS4 (child components receive props from here)
- **Artifact:** `admin-dev/themes/new-theme/js/pages/{domain}/{Domain}FormManager.vue`
- **PS example:** `admin-dev/themes/new-theme/js/pages/carrier/CarrierFormManager.vue`

## Instructions

1. Create `{Domain}FormManager.vue` with `<template>`, `<script setup lang="ts">`, `<style scoped>`.
2. Import child components (tab components, dynamic field components).
3. Define reactive state with `ref()` or `reactive()` for each dynamic form section.
4. For each Vue-managed field, use `watch()` to sync state to a hidden `<input type="hidden">` that Symfony form expects.
5. Pass state as props to child components via `:propName="state.field"`.
6. Listen to child `@update` events to mutate parent state.

## Rules

- State flows down as props, updates flow up as events (standard Vue pattern)
- Hidden input names must exactly match Symfony form field names
- Do not mix Vue reactivity with jQuery DOM manipulation
