---
name: create-ts-entry-point
brick: JS1
component: GlobalJS
step: 8
needs: [F1, A3]
produces: "admin-dev/themes/new-theme/js/pages/{domain}/index.ts — TypeScript entry point for the domain form"
conditional: false
---

# create-ts-entry-point

## Description
Create the TypeScript entry point that boots the Vue.js form manager for the entity's add/edit form. This file is the Webpack entry and must import and mount all Vue components for the domain.

## Context
- **Brick:** JS1 — Step 8
- **Reads from:** F1 (form structure to match), A3 (which Vue components are needed)
- **Writes to:** JS2–JS5 (imported and initialized from here)
- **Artifact:** `admin-dev/themes/new-theme/js/pages/{domain}/index.ts`
- **PS example:** `admin-dev/themes/new-theme/js/pages/carrier/index.ts`

## Instructions

1. Create `admin-dev/themes/new-theme/js/pages/{domain}/index.ts`.
2. Import Vue 3: `import {createApp} from 'vue'`.
3. Import the form manager component: `import {Domain}FormManager from './{Domain}FormManager.vue'`.
4. Mount: `const app = createApp({Domain}FormManager); app.mount('#app-{domain}-form')`.
5. Import the tab error navigator: `import initTabErrorNavigator from './tabErrorNavigator'`.
6. On DOMContentLoaded: `initTabErrorNavigator()`.
7. Register the entry point in `admin-dev/themes/new-theme/webpack.config.js` as a new entry: `'{domain}': './js/pages/{domain}/index.ts'`.

## Rules

- Entry point must be registered in webpack.config.js — untouched entries are not compiled
- Mount point ID must match the Twig template's container element (T2)
- Only import — do not inline component logic in the entry point
