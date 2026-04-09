---
name: legacy-to-symfony-migration
description: Step-by-step guide for migrating a PrestaShop Legacy admin page to Symfony/CQRS. Covers the full lifecycle from audit to GA, derived from the Carrier page migration case study.
type: skill
---

# Legacy → Symfony/CQRS Migration Skill

> Derived from the Carrier page migration (PRs #20737, #36063–#37638).
> Each phase is a separate file. Read them in order, or jump to the phase you need.

## When to use this skill

Trigger when asked to:
- "Migrate the Xxx admin page to Symfony"
- "Create CQRS for the Xxx domain"
- "Add a Symfony form for Xxx"
- "Migrate AdminXxxController"

## Related documents

| Document | Purpose |
|---|---|
| `.claude/legacy-to-symfony-migration-workflow.md` | PR timeline, PS-specific quirks, high-level checklist |
| `.claude/migration-micro-skill-architecture.md` | Full 69-brick decomposition with input/output contracts, dependency graph, conditional matrix |

## Phase index

| # | File | Title | Deliverable | Bricks |
|---|------|-------|-------------|--------|
| 0 | [step-00-audit.md](step-00-audit.md) | Audit | Field map, action list, milestone decision | A1, A2, A3 |
| 1 | [step-01-domain-layer.md](step-01-domain-layer.md) | Domain Layer | Commands, Queries, ValueObjects, Exceptions | D1–D14 |
| 2 | [step-02-adapter-layer.md](step-02-adapter-layer.md) | Adapter Layer | Repository, Handlers, Validator | P1–P10 |
| 3 | [step-03-behat-tests.md](step-03-behat-tests.md) | Behat Tests | Integration test coverage for CQRS | B1–B6 |
| 4 | [step-04-grid.md](step-04-grid.md) | Grid | Listing page with filters and bulk actions | G1–G5 |
| 5 | [step-05-symfony-controller.md](step-05-symfony-controller.md) | Symfony Controller | All admin actions wired to command/query bus | H1 |
| 6 | [step-06-routing.md](step-06-routing.md) | Routing | YAML routes with feature flag | H2 |
| 7 | [step-07-form.md](step-07-form.md) | Form | Tab-based add/edit form with DataProvider/Handler | F1–F6 |
| 8 | [step-08-frontend.md](step-08-frontend.md) | Frontend | TypeScript entry point and Vue components | JS1–JS5 |
| 9 | [step-09-twig-templates.md](step-09-twig-templates.md) | Twig Templates | index and form templates | T1–T4 |
| 10 | [step-10-feature-flag.md](step-10-feature-flag.md) | Feature Flag | Beta registration in feature_flag.xml | H3 |
| 11 | [step-11-playwright-tests.md](step-11-playwright-tests.md) | Playwright Tests | UI test campaigns per feature area | E1–E7 |
| 12 | [step-12-general-availability.md](step-12-general-availability.md) | General Availability | Promote flag to stable | R1–R3 |
| 13 | [step-13-legacy-deprecation.md](step-13-legacy-deprecation.md) | Legacy Deprecation | Banner in legacy controller | R4–R6 |

## Key PS-specific rules (always apply)

- Every repository extends `AbstractMultiShopObjectModelRepository` — multistore is never optional
- Sub-resources get their own Command, never merged into `EditXxxCommand`
- Feature flag is the routing mechanism, not a cosmetic toggle — every route must carry it
- Legacy controller is **never deleted**, only gets a deprecation banner
- `IdentifiableObject` DataProvider + DataHandler pattern replaces Symfony form events
- `NavigationTabType` for multi-tab forms — not standard Symfony tabs
