---
name: create-twig-breadcrumb
brick: "—"
component: Twig
step: 9
needs: [T1, T2]
produces: "Breadcrumb configuration for index and form pages"
conditional: false
---

# create-twig-breadcrumb

## Description
Documents how to configure the PrestaShop admin breadcrumb in Symfony templates. The breadcrumb is populated via the controller's `breadcrumbsAndTitle` block or via Twig.

## Context
- **Brick:** — — Step 9
- **Reads from:** T1, T2 (templates being edited), controller action
- **Writes to:** T1, T2 (adds breadcrumb/title blocks) + controller (breadcrumb setup)
- **Artifact:** Template blocks (edit T1, T2 output) + controller breadcrumb setup
- **PS example:** Any PS admin controller extending `FrameworkBundleAdminController`

## Instructions

1. In the controller action, call `$this->setBreadcrumbs(['Home', '{Section}', '{Domain}s'])` if that helper exists.
2. Alternatively, in the Twig template: `{% block page_title %}{{ 'Manage {Domain}s'|trans({}, 'Admin.{Section}.Feature') }}{% endblock %}`.
3. For form page: breadcrumb should be `Home > {Section} > {Domain}s > Edit`.
4. Check the PS breadcrumb helper in `FrameworkBundleAdminController` for the correct method name.

## Rules

- Page titles must be translatable
- Breadcrumb chain must reflect the navigation hierarchy
