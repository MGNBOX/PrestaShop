---
step: 0
title: "Audit"
skill: legacy-to-symfony-migration
previous: null
next: step-01-domain-layer.md
deliverable: "A written field map, action inventory, and milestone decision before writing any new code"
---

# Step 0 — Audit

Before touching a single new file, you must have a complete picture of what the legacy page does. Skipping this step leads to missing CQRS commands, incomplete form fields, and broken UI interactions discovered late.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **A1** | `audit-legacy-controller` | `controllers/admin/Admin{Domain}*.php` (read-only) | — |
| **A2** | `audit-object-model` | `classes/{Domain}.php`, `classes/lang/{Domain}Lang.php` (read-only) | — |
| **A3** | `generate-migration-manifest` | `migration-manifest.md` | — |

## 0.1 — Read the legacy controller

Open `controllers/admin/AdminXxxController.php`. Read it entirely.

PrestaShop legacy controllers inherit from `AdminController`. The things to identify:

- **`$this->table`** — the main DB table name (maps to the `ObjectModel` class)
- **`$this->className`** — the `ObjectModel` class name (`classes/Xxx.php`)
- **`$this->fields_list`** — every column shown in the listing: note names, types, `havingFilter`, `orderby`
- **`$this->bulk_actions`** — which bulk operations exist (`delete`, `enableSelection`, `disableSelection`)
- **`$this->actions`** — row-level actions (`edit`, `delete`, `view`)
- **`$this->fields_form`** — every form field in the add/edit form: input type, required, multilingual, validation
- Every `postProcess()`, `processSave()`, `processAdd()`, `processUpdate()`, `processDelete()`, `processBulk*()` method — these map 1:1 to the Commands you will create
- Any custom methods that load extra data for the form (e.g. `getGroups()`, `getZones()`) — these map to associated data or sub-resources

## 0.2 — Read the ObjectModel class

Open `classes/Xxx.php`. Record:

- **`$definition['fields']`** — every field with its type (`TYPE_INT`, `TYPE_STRING`, `TYPE_BOOL`, `TYPE_FLOAT`), `required`, `validate`, `lang` (multilingual), `size`
- **`$definition['multilang']`** — whether the entity has translated fields (`ps_xxx_lang` table)
- **`$definition['multishop']`** — whether the entity is multistore-aware (has `ps_xxx_shop` table)
- **`$definition['associations']`** — M:M relations (e.g. groups, zones, shops)
- The `deleted` field if present — means soft-delete is used
- The `active` field — toggleable status
- The `position` field — drag-and-drop reordering support

Pay attention to any custom `save()`, `delete()`, `add()`, `update()` overrides — they contain business rules that must be preserved in the new handlers.

## 0.3 — Categorise every data field

Group every field from `$definition['fields']` into one of these categories:

| Category | Description | Impact on migration |
|---|---|---|
| **Scalar** | Simple int/string/bool/float stored in main table | Straightforward Command property |
| **Multilingual** | Has `lang => true` — stored in `_lang` table | Command takes `array $localizedValues` keyed by language ID |
| **Association (M:M)** | Groups, zones, tags, shops | Stored in join table; Command takes `int[]`; may need dedicated `Set{Domain}{Association}Command` |
| **File upload** | Image, logo, document | Needs domain interface + adapter uploader; separate handling in command |
| **Sub-resource** | Has its own table with FK (e.g. ranges, prices per zone) | Needs dedicated Command + Repository + Behat feature file |
| **Computed/virtual** | Not stored, derived at query time | Query handler computes it; not in Command |

## 0.4 — Inventory all actions

Produce a table like this for the entity:

| Action | Legacy method | HTTP | Command to create |
|---|---|---|---|
| Create | `processAdd()` | POST | `AddXxxCommand` |
| Edit | `processUpdate()` | POST | `EditXxxCommand` |
| Delete | `processDelete()` | POST | `DeleteXxxCommand` |
| Bulk delete | `processBulkDelete()` | POST | `BulkDeleteXxxCommand` |
| Enable | `processBulkEnableSelection()` | POST | `BulkToggleXxxStatusCommand` |
| Disable | `processBulkDisableSelection()` | POST | `BulkToggleXxxStatusCommand` |
| Toggle status | (row action in list) | POST | `ToggleXxxStatusCommand` |
| Reorder | (position update) | POST | handled by `PositionDefinition` |
| Set sub-resource | custom method | POST | `SetXxx{SubResource}Command` |

Sub-resources that have their own table always get their own dedicated command — never merge them into `EditXxxCommand`.

## 0.5 — Check multistore implications

Examine the ObjectModel for `'multishop' => true` or `$this->multishop_table`. If it is multistore-aware:

- Every write handler must call `getShopIdsByConstraint()` from `AbstractMultiShopObjectModelRepository`
- The `add()` method must propagate the entity to all relevant shops
- There may be shop-scoped overrides (e.g. tax rule group per shop) — these become dedicated sub-resource commands

If multistore is **not** in scope for the first sprint, note it explicitly so it can be added later without breaking the command signatures.

## 0.6 — Identify hooks

Search the legacy controller and ObjectModel for `Hook::exec()` or `$this->context->hook->exec()` calls. List every hook dispatched so they can be preserved (or consciously dropped) in the new handlers.

## 0.7 — Decide milestone strategy

The Carrier page took **3 years** between listing migration (2021) and form migration (2024). Decide explicitly:

| Option | When to choose |
|---|---|
| **Single sprint** | Entity is simple (< 10 fields, no sub-resources, no multistore) |
| **Listing first, form later** | Entity is complex; listing unblocks bulk actions immediately |
| **CQRS first, then UI** | Team wants the API layer solid before building the interface |

Document the decision in the PR description so reviewers understand the scope boundary.

## Checklist

- [ ] `AdminXxxController.php` read entirely
- [ ] `classes/Xxx.php` read entirely, all fields categorised
- [ ] Action inventory table completed
- [ ] Multilingual fields identified
- [ ] Associations (M:M) identified
- [ ] Sub-resources with own tables identified
- [ ] Multistore status confirmed
- [ ] Hooks listed
- [ ] Milestone strategy decided and documented
