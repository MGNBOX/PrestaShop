---
name: generate-migration-manifest
brick: A3
component: Migration
step: 0
needs: [A1, A2]
produces: "migration-manifest.md — authoritative spec listing all commands, queries, form tabs, grid columns, hooks, and sub-resources"
conditional: false
---

# generate-migration-manifest

## Description
Synthesize the outputs of A1 and A2 into a single `migration-manifest.md` that serves as the migration specification — every subsequent brick reads from this document to know what to create.

## Context
- **Brick:** A3 — Step 0
- **Reads from:** A1 output (controller audit) and A2 output (ObjectModel audit)
- **Writes to:** All downstream bricks (D1–D14, G1–G5, F1–F6, etc.) read this manifest
- **Artifact:** `migration-manifest.md` (created at project/domain root or in a docs folder)
- **PS example:** No pre-existing file — created fresh per domain

## Instructions

1. Create `migration-manifest.md` at an agreed location (e.g., `docs/migration/{domain}-manifest.md` or project root).
2. Section 1 — Commands: list Add, Edit, Delete, BulkDelete, BulkToggleStatus, ToggleStatus for the domain. Mark each as required or conditional.
3. Section 2 — Queries: list GetForEditing (returns edit DTO) and GetList (for grid). Note any additional queries (e.g., GetForView).
4. Section 3 — Form tabs: list each tab with its fields. For each field: name, type, translatable (Y/N), required (Y/N), validation rule.
5. Section 4 — Grid columns: list each column with its type (DataColumn, ToggleColumn, ActionColumn, PositionColumn).
6. Section 5 — Grid filters: list each filter with its type (TextFilter, SelectFilter, DateRangeFilter, etc.).
7. Section 6 — Sub-resources: list any has-many relations (e.g., carrier ranges, carrier zones) that require D14/P10.
8. Section 7 — Hooks: list all legacy hooks with their Symfony equivalents or note "no equivalent yet".
9. Section 8 — Milestone decision: based on complexity, propose which PRs (domain+adapter, grid, form tabs 1/2/3, GA) this migration should be split into.

## Rules

- This document is the single source of truth — all subsequent bricks must reference it, not the legacy files
- Every field listed in A2 must appear in either a form tab or a grid column in the manifest
- Mark sub-resources explicitly — missing a sub-resource here causes silent data loss in handlers
- The milestone decision must produce at least 3 PRs (domain layer, grid, form) — never merge all in one PR
