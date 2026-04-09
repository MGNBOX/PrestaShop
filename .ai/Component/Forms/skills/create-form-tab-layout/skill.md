---
name: create-form-tab-layout
brick: F4
component: Forms
step: 7
needs: [F1]
produces: "NavigationTabType-based tab structure with correct anchor IDs and tab error classes"
conditional: false
---

# create-form-tab-layout

## Description
Documents the PrestaShop-specific tab layout pattern using `NavigationTabType`. Each form tab is a separate sub-form type embedded in the root form type via NavigationTabType.

## Context
- **Brick:** F4 — Step 7
- **Reads from:** F1 (root form type)
- **Writes to:** F1 (tab sub-form types are embedded in root form type)
- **Artifact:** Form type files (edit F1 output) + tab sub-form types
- **PS example:** Check any multi-tab PS form (e.g., Carrier form tabs)

## Instructions

1. In root `{Domain}Type::buildForm()`: add each tab using `NavigationTabType`:
   ```php
   ->add('generalTab', NavigationTabType::class, [
       'tab_title' => $this->trans('General', [], 'Admin.Global'),
   ])
   ```
2. Create one form type per tab: `{Domain}GeneralTabType.php`, `{Domain}ShippingTabType.php`, etc.
3. Each tab type is an `AbstractType` with its own `buildForm()` containing only that tab's fields.
4. The tab anchor IDs (used for error scrolling in JS5) are derived from the tab names.
5. Never use Symfony's standard `TabsType` — always use `NavigationTabType`.

## Rules

- NavigationTabType is PS-specific — do not replace with Symfony alternatives
- Each tab gets its own form type class for maintainability
- Tab title must use the `$this->trans()` method for translation
