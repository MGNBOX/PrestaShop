---
step: 6
title: "Routing"
previous: step-05-symfony-controller.md
next: step-07-form.md
deliverable: "YAML routing file with all routes carrying _legacy_feature_flag, registered in parent routing.yml"
---

# Step 6 — Routing

Routing in PrestaShop admin follows a specific convention that differs from standard Symfony. The most critical rule: **every route on a migrated page must carry `_legacy_feature_flag`**. This annotation is the mechanism that routes the request to either the new Symfony controller or the old legacy controller — it is not a cosmetic toggle.

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `create-admin-routing` | `Resources/config/routing/admin/{section}/{domain}s.yml` | — |

> **Routing must be committed together with `create-admin-controller` and `register-feature-flag`** — routes referencing an unregistered feature flag cause a 500.

## 6.1 — File location and naming

Create (or extend) the routing YAML at:

```
src/PrestaShopBundle/Resources/config/routing/admin/{section}/{subsection}/{domain}s.yml
```

Examples:
- `routing/admin/improve/shipping/carriers.yml`
- `routing/admin/sell/catalog/products.yml`
- `routing/admin/configure/advanced/employees.yml`

## 6.2 — Route naming convention

All route names follow: `admin_{domain}s_{action}` (plural domain, snake_case action).

| Action | Route name | Method(s) |
|---|---|---|
| Listing | `admin_{domain}s_index` | GET |
| Search/filter | `admin_{domain}s_search` | POST |
| Create form | `admin_{domain}s_create` | GET, POST |
| Edit form | `admin_{domain}s_edit` | GET, POST |
| Delete | `admin_{domain}s_delete` | POST, DELETE |
| Toggle status | `admin_{domain}s_toggle_status` | POST |
| Toggle free/other bool | `admin_{domain}s_toggle_{field}` | POST |
| Bulk enable | `admin_{domain}s_bulk_enable_status` | POST |
| Bulk disable | `admin_{domain}s_bulk_disable_status` | POST |
| Bulk delete | `admin_{domain}s_bulk_delete` | POST, DELETE |
| Update position | `admin_{domain}s_update_position` | POST |

## 6.3 — Full YAML example

```yaml
# src/PrestaShopBundle/Resources/config/routing/admin/improve/shipping/{domain}s.yml

admin_{domain}s_index:
    path: /
    methods: [GET]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::indexAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_search:
    path: /search
    methods: [POST]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::searchAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_create:
    path: /create
    methods: [GET, POST]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::createAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_edit:
    path: /{domainId}/edit
    methods: [GET, POST]
    requirements:
        domainId: \d+
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::editAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_delete:
    path: /{domainId}/delete
    methods: [POST, DELETE]
    requirements:
        domainId: \d+
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::deleteAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_toggle_status:
    path: /{domainId}/toggle-status
    methods: [POST]
    requirements:
        domainId: \d+
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::toggleStatusAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_bulk_enable_status:
    path: /bulk-enable
    methods: [POST]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::bulkEnableStatusAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_bulk_disable_status:
    path: /bulk-disable
    methods: [POST]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::bulkDisableStatusAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_bulk_delete:
    path: /bulk-delete
    methods: [POST, DELETE]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::bulkDeleteAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}

admin_{domain}s_update_position:
    path: /update-position
    methods: [POST]
    defaults:
        _controller: PrestaShopBundle\Controller\Admin\Improve\Shipping\{Domain}Controller::updatePositionAction
        _legacy_controller: Admin{Domain}s
        _legacy_feature_flag: {domain}
```

## 6.4 — How `_legacy_feature_flag` works

When a request arrives at a route with `_legacy_feature_flag: {domain}`:

1. PrestaShop checks if the feature flag named `{domain}` is enabled in `feature_flag` table
2. **If enabled**: the request is handled by the Symfony `_controller` declared in the route
3. **If disabled**: the request is redirected to the legacy controller named in `_legacy_controller`

This means:
- The feature flag **must** be registered in `feature_flag.xml` before the routes will work in either direction (Step 10)
- During development (before the flag is registered), the Symfony controller is accessible directly but the fallback won't work
- Setting `_legacy_feature_flag` without registering the flag entry causes a 500 error — register them together

## 6.5 — `_legacy_link` for menu integration

The admin menu uses `_legacy_link` to map legacy controller names to new Symfony routes. When cleaning up legacy links, add an entry in the link configuration so that `Link::getAdminLink('Admin{Domain}s')` resolves to the new Symfony route when the flag is on:

```yaml
# This is typically done in a dedicated "clean legacy links" PR
# src/PrestaShopBundle/Resources/config/routing/admin/legacy_link_routes.yml
Admin{Domain}s:
    route: admin_{domain}s_index
    feature_flag: {domain}
```

## 6.6 — Register in parent routing file

Add an import in the parent routing file for the section:

```yaml
# src/PrestaShopBundle/Resources/config/routing/admin/improve/shipping.yml
{domain}s:
    resource: shipping/{domain}s.yml
    prefix: /{domain}s
```

The `prefix` determines the URL base. For carriers: `/carriers`. For products: `/products`. The prefix is stripped from individual route paths.

## 6.7 — URL parameter naming

Use camelCase for route parameters matching the controller argument names:
- `{domainId}` in the path → `int $domainId` in the controller action
- Always add `requirements: { domainId: \d+ }` to prevent string injection

## Checklist

- [ ] Routing YAML file created at the correct path for the admin section
- [ ] All actions have routes: index, search, create, edit, delete, bulk delete, bulk enable/disable, toggle status, update position (if applicable)
- [ ] Every route has `_legacy_feature_flag: {domain}`
- [ ] Every route has `_legacy_controller: Admin{Domain}s`
- [ ] Route parameters have `\d+` requirements
- [ ] Routing file imported in the parent section routing YAML with correct prefix
- [ ] Legacy link mapping added (or noted for the cleanup PR)
- [ ] Routes verified by running `php bin/console debug:router | grep {domain}`
