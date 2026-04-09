---
name: create-tab-error-navigator
brick: JS5
component: GlobalJS
step: 8
needs: [JS1, F1]
produces: "tabErrorNavigator.ts — module that switches to the first form tab containing a server-side validation error"
conditional: false
---

# create-tab-error-navigator

## Description
Create the TypeScript module that, on page load after a form submission with validation errors, detects which tab contains invalid fields and activates that tab automatically. This prevents users from seeing a success-looking form when errors exist in a hidden tab.

## Context
- **Brick:** JS5 — Step 8
- **Reads from:** F4 (tab structure defines which CSS selectors to scan), F1 (field IDs)
- **Writes to:** Activates the correct tab in the NavigationTabType UI
- **Artifact:** `admin-dev/themes/new-theme/js/pages/{domain}/tabErrorNavigator.ts`
- **PS example:** Check any PS multi-tab form for tab error navigation JS

## Instructions

1. Export `initTabErrorNavigator(): void` function.
2. On DOMContentLoaded, query all tab pane elements.
3. For each tab pane, check if it contains any `is-invalid` CSS class (Symfony form error class).
4. If a tab pane has errors, activate it by triggering click on its nav tab or calling the Bootstrap Tab API.
5. Activate only the FIRST tab with errors — stop after finding the first.
6. If no errors found, do nothing.

## Rules

- Only run on form pages that have NavigationTabType tabs
- Must run AFTER Symfony's form error classes are in the DOM (run on DOMContentLoaded)
- Never modify form data — this is read-only navigation only
