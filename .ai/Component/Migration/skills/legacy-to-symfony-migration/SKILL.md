---
name: legacy-to-symfony-migration
description: >
  Step-by-step guide for migrating a PrestaShop Legacy admin page to Symfony/CQRS.
  Covers the full lifecycle from audit to GA. Trigger: "migrate the Xxx admin page",
  "create CQRS for Xxx", "add a Symfony form for Xxx", "migrate AdminXxxController".
---

# Legacy to Symfony/CQRS Migration Skill

Read `@.ai/Component/Migration/CONTEXT.md` for conventions, reference pages, dependency graph, and conditional activation matrix.

## When to use this skill

Trigger when asked to:
- "Migrate the Xxx admin page to Symfony"
- "Create CQRS for the Xxx domain"
- "Add a Symfony form for Xxx"
- "Migrate AdminXxxController"

## Phase index

| # | File | Title | Deliverable |
|---|------|-------|-------------|
| 0 | [step-00-audit.md](step-00-audit.md) | Audit | Field map, action list, milestone decision |
| 1 | [step-01-domain-layer.md](step-01-domain-layer.md) | Domain Layer | Commands, Queries, ValueObjects, Exceptions |
| 2 | [step-02-adapter-layer.md](step-02-adapter-layer.md) | Adapter Layer | Repository, Handlers, Validator |
| 3 | [step-03-behat-tests.md](step-03-behat-tests.md) | Behat Tests | Integration test coverage for CQRS |
| 4 | [step-04-grid.md](step-04-grid.md) | Grid | Listing page with filters and bulk actions |
| 5 | [step-05-symfony-controller.md](step-05-symfony-controller.md) | Symfony Controller | All admin actions wired to command/query bus |
| 6 | [step-06-routing.md](step-06-routing.md) | Routing | YAML routes with feature flag |
| 7 | [step-07-form.md](step-07-form.md) | Form | Tab-based add/edit form with DataProvider/Handler |
| 8 | [step-08-frontend.md](step-08-frontend.md) | Frontend | JS entry point (initComponents or Vue) |
| 9 | [step-09-twig-templates.md](step-09-twig-templates.md) | Twig Templates | index and form templates |
| 10 | [step-10-feature-flag.md](step-10-feature-flag.md) | Feature Flag | Beta registration in feature_flag.xml |
| 11 | [step-11-playwright-tests.md](step-11-playwright-tests.md) | Playwright Tests | UI test campaigns per feature area |
| 12 | [step-12-general-availability.md](step-12-general-availability.md) | General Availability | Promote flag to stable |
| 13 | [step-13-legacy-deprecation.md](step-13-legacy-deprecation.md) | Legacy Deprecation | Banner in legacy controller |
