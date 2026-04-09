---
name: register-cqrs-services
brick: —
component: CQRS
step: 2
needs: [D9, D10, P2, P3, P4, P5, P6, P7, P8, P9, P10, P1]
produces: "DI YAML service registrations for all handlers and repository"
conditional: false
---

# register-cqrs-services

## Description
Register all domain handlers and the repository in the Symfony DI container. Commands and queries route to handlers via command/query bus tags.

## Context
- **Brick:** — — Step 2
- **Reads from:** All handler classes (P2–P10) and repository (P1) to register
- **Writes to:** Symfony DI container — makes all handlers callable via the command/query bus
- **Artifact:** `src/PrestaShopBundle/Resources/config/services/{domain}.yml` or inline in existing services files
- **PS example:** `src/PrestaShopBundle/Resources/config/services/` (check carrier services)

## Instructions

1. Find the correct YAML file (check where other domain services are registered — may be per-domain or grouped).
2. Register `{Domain}Repository` with `autowire: true`.
3. For each handler, register the concrete class and tag it with `prestashop.command_handler` or `prestashop.query_handler`.
4. Tag format: `{ name: 'tactician.handler', command: 'PrestaShop\Core\Domain\{Domain}\Command\Add{Domain}Command' }`.
5. Register handler interfaces and bind concrete implementations.
6. Run `php bin/console debug:container | grep {domain}` to verify registration.

## Rules

- All services must be explicitly registered or use `autowire: true` — no magic
- Each handler tagged exactly once — duplicate tags cause multiple handler errors
- Check the tactician bus configuration for the correct tag format
