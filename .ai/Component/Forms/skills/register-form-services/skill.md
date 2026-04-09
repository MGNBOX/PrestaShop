---
name: register-form-services
brick: F6
component: Forms
step: 7
needs: [F1, F2, F3]
produces: "DI YAML registrations for form type, data provider, and data handler"
conditional: false
---

# register-form-services

## Description
Register the form type, data provider, and data handler in the Symfony DI container. Ensures all form layer services are wired and discoverable by the controller and form factory.

## Context
- **Brick:** F6 — Step 7
- **Reads from:** F1 ({Domain}Type class), F2 ({Domain}FormDataProvider class), F3 ({Domain}FormDataHandler class)
- **Writes to:** H1 (controller injects registered services), Symfony container
- **Artifact:** `src/PrestaShopBundle/Resources/config/services/{domain}.yml`
- **PS example:** Check existing service YAML files under `src/PrestaShopBundle/Resources/config/services/`

## Instructions

1. Register `{Domain}Type` with `tag: form.type`.
2. Register `{Domain}FormDataProvider` with `autowire: true`.
3. Register `{Domain}FormDataHandler` with `autowire: true`.
4. Inject `CommandBus` and `QueryBus` into provider/handler if not autowired.
5. Run `php bin/console debug:container | grep {domain}_form` to verify.

## Rules

- All three form layer services (type, provider, handler) must be registered before wiring the controller
- Use autowire where possible — only add explicit arguments for bus injections
- Service IDs follow the pattern: `prestashop.core.form.identifiable_object.{domain}.data_provider`
