# PrestaShop Migration — Micro-Skill Architecture

> Granular decomposition of the Legacy → Symfony/CQRS migration workflow into 69 atomic "Lego bricks".
> Each brick has a single responsibility, a precise input contract, and a precise output contract.
> Source: `.ai/skills/legacy-to-symfony-migration/` (Steps 00–13)

---

## Legend

| Column | Meaning |
|---|---|
| **ID** | Short reference code used in the Input chain |
| **Micro-Skill** | The brick name (`kebab-case`) |
| **Artifact** | The single file (or XML entry) it creates or edits |
| **Needs** | Which brick IDs must be complete before this runs |
| **Produces** | The precise contract it hands to downstream bricks |
| **⚠** | Conditional — only activated when the flag is true |

---

## GROUP A — Audit (Step 00)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **A1** | `audit-legacy-controller` | read-only | `controllers/admin/Admin{Domain}*.php` | Action list: `[{name, legacy_method, http_method, data_written}]`; boolean field list from `$this->fields_list` | — |
| **A2** | `audit-object-model` | read-only | `classes/{Domain}.php`, `classes/lang/{Domain}Lang.php` | Field inventory: `[{name, type, category, required, lang, size}]`, association list, `multistore: bool`, `softDelete: bool`, `hasPosition: bool`, hook list | — |
| **A3** | `generate-migration-manifest` | `migration-manifest.md` | A1, A2 | **The canonical input document for all downstream bricks**: entity name, table, field table (scalar / i18n / M:M / file / sub-resource), action→command map, sub-resource list with their own tables, multistore flag, milestone split decision | — |

---

## GROUP D — Domain Layer (Step 01)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **D1** | `create-identity-value-object` | `ValueObject/{Domain}Id.php` | A3: entity name, PK type | `{Domain}Id` class; positive-int guard throwing `{Domain}ConstraintException::INVALID_ID` | — |
| **D2** | `create-semantic-value-object` | `ValueObject/{Concept}.php` ×N | A3: enum-like field list | N VO classes with typed `const` values and `fromInt()/fromString()` factory | if enums |
| **D3** | `create-add-command` | `Command/Add{Domain}Command.php` | A3: required + optional field list, i18n list, association list; D1 | Command: typed constructor (required fields) + fluent setters (optional) + `int[]` for associations + `array` for i18n | — |
| **D4** | `create-edit-command` | `Command/Edit{Domain}Command.php` | D3: same field list; D1 | Command: `{Domain}Id` constructor + **nullable** setter for every field (partial-update pattern) | — |
| **D5** | `create-delete-commands` | `Command/Delete{Domain}Command.php` + `BulkDelete{Domain}Command.php` | D1 | Two command classes; bulk takes `{Domain}Id[]` | — |
| **D6** | `create-toggle-commands` | `Command/Toggle{Domain}StatusCommand.php` + `BulkToggle{Domain}StatusCommand.php` | D1; A3: boolean toggle fields | Two command classes per toggle field (status, isFree, etc.) | — |
| **D7** | `create-sub-resource-command` | `Command/Set{Domain}{SubRes}Command.php` ×N | D1; A3: sub-resource field shapes | N commands — each takes `{Domain}Id` + full new state collection (atomic replace, no partial) | if sub-res |
| **D8** | `create-get-for-editing-query` | `Query/Get{Domain}ForEditing.php` | D1 | Query class carrying `{Domain}Id` | — |
| **D9** | `create-list-query` | `Query/Get{Domain}s.php` | A3: filterable fields | Query class with optional filter params | — |
| **D10** | `create-editable-dto` | `QueryResult/Editable{Domain}.php` | A3: all fields with types + nullable flags; D1; D2: enum VO types | Immutable DTO: typed `readonly` constructor + getters only; `int[]` for associations, `array` for i18n, `null` for optional | — |
| **D11** | `create-exception-hierarchy` | `Exception/{Domain}Exception.php` + `{Domain}ConstraintException.php` + `CannotXxx*.php` ×N | A3: action list + field list | Base exception; `ConstraintException` with one `const INVALID_*` per field rule; one `CannotAdd/Update/Delete/Toggle` per write action | — |
| **D12** | `create-command-handler-interfaces` | `CommandHandler/{Action}{Domain}HandlerInterface.php` ×N | D3–D7: all command classes | N handler interfaces — one per command, each with single `handle(XxxCommand): void\|{Domain}Id` | — |
| **D13** | `create-query-handler-interfaces` | `QueryHandler/{Query}{Domain}HandlerInterface.php` ×N | D8–D9: all query classes; D10 | M handler interfaces — one per query, each with `handle(XxxQuery): Editable{Domain}\|array` | — |
| **D14** | `create-file-uploader-interface` | `{Domain}LogoFileUploaderInterface.php` | A3: upload field name | Domain interface: `upload(string $path, int $id): void` + `delete(int $id): void` | if file uploads |

---

## GROUP P — Adapter Layer (Step 02)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **P1** | `create-domain-repository` | `Repository/{Domain}Repository.php` | D1; D11: exception classes; A3: table name, multistore flag | `AbstractMultiShopObjectModelRepository` subclass: `get()`, `add(+shopIds)`, `update(+shopConstraint)`, `delete()`, all calling `getShopIdsByConstraint()` | — |
| **P2** | `create-sub-resource-repository` | `Repository/{Domain}{SubRes}Repository.php` ×N | A3: sub-resource table schema; D1; D11 | Atomic-replace repo: `findByParent({Domain}Id)`, `save(collection, {Domain}Id)` = delete-all then insert-all | if sub-res |
| **P3** | `create-domain-validator` | `Validate/{Domain}Validator.php` | D11: `ConstraintException` const codes; A3: field rules (size, regex, required) | Validator: per-field methods + `$entity->validateFields()` wrapper; every violation throws typed `ConstraintException` | — |
| **P4** | `create-add-command-handler` | `CommandHandler/Add{Domain}Handler.php` | D3; D12; P1; P3; D14 (if uploads) | Handler: `fillEntityFromCommand()`, `$validator->validate()`, `$repository->add()`, association setters post-save, logo uploader call | — |
| **P5** | `create-edit-command-handler` | `CommandHandler/Edit{Domain}Handler.php` | D4; D12; P1; P3 | Handler: loads entity, `if ($cmd->getName() !== null)` pattern for every field, validates, `$repository->update()` | — |
| **P6** | `create-delete-command-handlers` | `CommandHandler/Delete{Domain}Handler.php` + `BulkDelete{Domain}Handler.php` | D5; D12; P1 | Two handlers; single delete checks `deleted` flag; bulk iterates `{Domain}Id[]` | — |
| **P7** | `create-toggle-command-handlers` | `CommandHandler/Toggle{Domain}StatusHandler.php` + bulk variant ×N | D6; D12; P1 | Two handlers per toggle field: flip boolean, `$repository->update()` | — |
| **P8** | `create-sub-resource-command-handler` | `CommandHandler/Set{Domain}{SubRes}Handler.php` ×N | D7; D12; P2 | Handler: `$repo->deleteByParent(id)` then `$repo->save(newCollection, id)` — always atomic | if sub-res |
| **P9** | `create-get-for-editing-handler` | `QueryHandler/Get{Domain}ForEditingHandler.php` | D8; D13; D10; P1 | Handler: loads entity, casts every property to typed PHP (`(bool)`, `(int)`, `array_map('intval', ...)`), returns `new Editable{Domain}(...)` | — |
| **P10** | `create-file-uploader-implementation` | `File/Uploader/{Domain}LogoFileUploader.php` | D14 | Concrete uploader: MIME validation, move to `_PS_IMG_DIR_`, thumbnail generation, throws `{Domain}LogoUploadFailedException` on any failure | if file uploads |

---

## GROUP B — Behat Integration Tests (Step 03)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **B1** | `create-behat-feature-context` | `Context/Domain/{Domain}/{Domain}FeatureContext.php` | D3–D9: command+query signatures; D10: DTO getters; D11: exception codes | Context class: step defs for add, edit, delete, toggle, get, constraint violations; string `$reference` keys for entity tracking via `CommandBus`/`QueryBus` | — |
| **B2** | `write-behat-crud-scenarios` | `Scenario/{Domain}/{domain}_management.feature` | B1 | Scenarios: add-minimum, add-all-fields, edit, delete, toggle status, bulk toggle | — |
| **B3** | `write-behat-constraint-scenarios` | append to `{domain}_management.feature` | D11: all `const INVALID_*` codes; B1 | One scenario per `ConstraintException` code — negative IDs, empty required fields, out-of-range values | — |
| **B4** | `write-behat-i18n-scenarios` | append to `{domain}_management.feature` | A3: i18n field list; B1 | Scenarios: create with per-language values, assert each locale independently | if i18n |
| **B5** | `write-behat-sub-resource-scenarios` | `Scenario/{Domain}/{domain}_{subres}.feature` ×N | A3: sub-resource structure; D7; B1 | Scenarios: set, replace atomically (3 rows → 1), set empty, assert collection counts | if sub-res |
| **B6** | `write-behat-multistore-scenarios` | `Scenario/{Domain}/{domain}_multishop.feature` | A3: multistore + association flags; B1 | Scenarios: add in all shops, edit scoped to one shop, per-shop isolation assertion | if multistore |

---

## GROUP G — Grid (Step 04)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **G1** | `create-grid-definition-factory` | `Grid/Definition/Factory/{Domain}GridDefinitionFactory.php` | A3: column list, boolean fields, image field, position flag, bulk+row actions; H2 route names (forward ref) | DefinitionFactory: all columns (`DataColumn`, `ToggleColumn`, `ImageColumn`, `PositionColumn`), bulk actions (enable/disable/delete), row actions (edit/delete), filters | — |
| **G2** | `create-grid-query-builder` | `Grid/Query/{Domain}QueryBuilder.php` | A3: table name, column names, soft-delete flag, filterable fields | DBAL `DoctrineQueryBuilderInterface`: `getSearchQueryBuilder()` + `getCountQueryBuilder()`; `deleted=0` in base query; `LIKE` for text, `=` for booleans | — |
| **G3** | `create-grid-data-factory` | `Grid/Data/Factory/{Domain}GridDataFactory.php` | A3: computed/image columns; G2 (inner decorator) | `GridDataFactoryInterface` decorator: resolves image URLs from IDs, formats computed columns | if computed cols |
| **G4** | `create-grid-search-filters` | `Core/Search/Filters/{Domain}Filters.php` | A3: filterable field names, default sort | `Filters` subclass: `$filterId = '{domain}'`, `getDefaults()` with limit/offset/orderBy/sortOrder | — |
| **G5** | `register-grid-services` | `services/core/grid/{domain}.yml` | G1–G4: class FQCNs; A3: position flag | YAML: definition factory (`parent: abstract`), query builder, data factory (+ `decorates:` if G3), search filters, position definition service | — |

---

## GROUP H — HTTP Layer (Steps 05 + 06 + 10)

> **H1 + H2 + H3 are always committed atomically.** A route with `_legacy_feature_flag: X` and no XML entry for `X` causes a 500.

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **H1** | `create-admin-controller` | `Controller/Admin/{Section}/{Domain}Controller.php` | D5–D8: commands+query; D11: exception classes; G4: `{Domain}Filters` arg; G5: grid factory service ID; F6: form builder + handler service IDs; A3: section path | Controller extending `PrestaShopAdminController`: all actions with `#[AdminSecurity]`+`#[DemoRestricted]`, `getErrorMessages()` map, JSON toggle responses, `getBulkActionIds()`, `updatePositionAction()` | — |
| **H2** | `create-admin-routing` | `Resources/config/routing/admin/{section}/{domain}s.yml` | H1: controller FQCN + action names; A3: flag name, entity param name | YAML: all routes carrying `_legacy_feature_flag: {domain}` + `_legacy_controller: Admin{Domain}s`; `\d+` requirements on ID params; imported in parent routing file with URL prefix | — |
| **H3** | `register-feature-flag` | `install-dev/data/xml/feature_flag.xml` (new entry) | A3: flag name; H2: confirms flag name spelling | XML entry: `stability="beta"`, `state="0"`, `label_wording`, `description_wording` in correct `Admin.*` translation domain | — |

---

## GROUP F — Form (Step 07)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **F1** | `create-root-form-type` | `Form/Admin/{Section}/{Domain}/{Domain}Type.php` | A3: tab list; F2: tab type class names (forward ref) | Root `TranslatorAwareType` using `getParent(): NavigationTabType`; one `->add(tabName, TabType::class)` per logical tab | — |
| **F2** | `create-tab-form-type` | `Form/Admin/{Section}/{Domain}/{Tab}SettingsType.php` ×N | A3: fields per tab; D10: DTO field types; D2: VO values for choices; A3: multistore flag | One `TranslatorAwareType` per tab; uses `SwitchType`, `TranslatableType`, `EntitySearchInputType`, `ShopChoiceTreeType` as required | — |
| **F3** | `create-complex-form-subtype` | `Form/Admin/{Section}/{Domain}/Type/{Domain}{Field}Type.php` ×N | A3: dynamic field data shape; JS2 (forward ref): placeholder div ID + `data-*` names | PHP bridge type: `buildForm()` adds `HiddenType`; `buildView()` sets `$view->vars['initial_json']`; `configureOptions()` declares data options | if Vue |
| **F4** | `create-form-data-provider` | `Core/Form/IdentifiableObject/DataProvider/{Domain}FormDataProvider.php` | D8: query class; D10: all DTO getters; F2: exact nested array key structure | DataProvider: `getData(int $id): array` dispatching `Get{Domain}ForEditing`, mapping DTO → nested array; `getDefaultData(): array` | — |
| **F5** | `create-form-data-handler` | `Core/Form/IdentifiableObject/DataHandler/{Domain}FormDataHandler.php` | D3: `Add{Domain}Command` constructor + setters; D4: `Edit{Domain}Command` setters; D7: sub-resource commands; F4: nested array keys (inverse) | DataHandler: `create(array $data): {Domain}Id` + `update(int $id, array $data): void`; dispatches primary command then all sub-resource commands sequentially | — |
| **F6** | `register-form-services` | `services/bundle/form/` YAML files (entries) | F1–F5: FQCNs | DI: `form.type` tag for F1+F2; DataProvider bean; DataHandler bean; FormBuilder factory; FormHandler factory | — |

---

## GROUP JS — Frontend (Step 08)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **JS1** | `create-form-entry-point` | `js/pages/{domain}/form/index.ts` | JS2: manager class name | Entry point: `new {Domain}FormManager().init()` | if Vue |
| **JS2** | `create-form-manager` | `js/pages/{domain}/form/{domain}-form-manager.ts` | F3: placeholder div ID + `data-*` names; F5: hidden input `name` for Vue→PHP sync | Manager: reads `data-*` JSON, mounts Vue via `createApp()`, implements `syncToHiddenField(data)` | if Vue |
| **JS3** | `create-vue-component` | `js/pages/{domain}/form/components/{Domain}{Field}.vue` ×N | A3: sub-resource data shape; F3: initial data structure + `onUpdate` prop contract | Vue SFC: reactive `ref<Row[]>`, `addRow()`, `removeRow(i)`, `watch(rows, onUpdate, {deep: true})` | if Vue |
| **JS4** | `register-webpack-entry` | webpack config (edit) | JS1: entry file path; A3: domain slug | `'{domain}-form': path.resolve(...)` in entry object | if Vue |
| **JS5** | `implement-tab-error-navigation` | append to `js/pages/{domain}/form/index.ts` | F1: tab pane IDs from `NavigationTabType` | On DOMContentLoaded: find first `.has-error`/`.is-invalid`, traverse to `.tab-pane`, click its `[data-target]` trigger — **never conditional, always include** | — |

---

## GROUP T — Twig Templates (Step 09)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **T1** | `create-index-template` | `views/Admin/{Section}/{Domain}s/index.html.twig` | H1: vars from `indexAction()` (`{domain}Grid`, `showcaseCardName`); H2: `admin_{domain}s_create` route | Template: extends layout, `grid_panel.html.twig` include, "Add new" toolbar button, optional showcase card | — |
| **T2** | `create-form-template` | `views/Admin/{Section}/{Domain}s/form.html.twig` | H1: vars from `createAction()`/`editAction()`; H2: create+edit routes; JS4: bundle asset name; F3: form type block name for `form_theme` | Template: `form_start` with dynamic action, `enctype` if file uploads, `form_widget`, Save+Cancel, JS bundle `<script>` | — |
| **T3** | `create-form-theme-widget` | `views/.../FormTheme/{widget}.html.twig` ×N | F3: Twig block name; JS2: placeholder div ID + `data-*` attribute names | Form theme block: `<div id="..." data-*="{{ form.vars.xxx\|json_encode }}">` + `{{ form_widget(form.hidden_field) }}` | if Vue |
| **T4** | `create-showcase-card` | `views/.../Blocks/showcase_card.html.twig` | A3: entity name; PS docs URL | Showcase card block using `@PrestaShop/Admin/Common/Layout/Components/showcase_card.html.twig` | if new feature |

---

## GROUP E — Playwright E2E (Step 11)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **E1** | `create-test-data-fixtures` | `tests/UI/data/{domain}.ts` | A3: all fields, types, valid/invalid values | Typed fixture object: `.minimal`, `.full`, `.toUpdate`, `.invalid` | — |
| **E2** | `create-test-resetter` | `tests/Resources/Resetter/{Domain}Resetter.php` | A3: table name; D5: delete command or direct DBAL | PHP resetter: deletes test entities by name/reference; called in Playwright `afterAll` | — |
| **E3** | `create-playwright-crud-campaign` | `tests/UI/.../01_CRUD{Domain}.ts` | E1; E2; H2: route names; H3: flag name; T1+T2: selectors | Campaign: create (all fields) → verify in listing → edit → verify updated → delete → verify removed | — |
| **E4** | `create-playwright-filter-campaign` | `tests/UI/.../02_filterSort{Domain}s.ts` | G1: filter column names; E1; H3 | Campaign: filter by each column, assert row count, sort asc/desc, reset filter | — |
| **E5** | `create-playwright-bulk-campaign` | `tests/UI/.../03_quickEditAndBulkActions.ts` | G1: bulk action names; H2: bulk routes; E1 | Campaign: bulk enable/disable/delete; single-row toggle (AJAX, no reload assert) | — |
| **E6** | `create-playwright-position-campaign` | `tests/UI/.../04_changePosition.ts` | G1: position column present; H2: update-position route | Campaign: drag row N to position M, assert swap in DOM | if position |
| **E7** | `create-playwright-tab-campaign` | `tests/UI/.../0{N}_{TabName}.ts` ×N | F2: field names per tab; T2: form selectors; E1; H3 | One campaign per form tab: create min entity, edit to fill tab, save, assert all fields persisted | per tab |

---

## GROUP R — Release (Steps 12–13)

| ID | Micro-Skill | Artifact | Needs | Produces | ⚠ |
|---|---|---|---|---|---|
| **R1** | `promote-feature-flag-to-stable` | `install-dev/data/xml/feature_flag.xml` (edit) | H3: flag ID; E3–E7: all campaigns green; QA sign-off | `stability="stable"`, `state="1"` on existing XML entry | — |
| **R2** | `write-upgrade-sql` | `upgrade/sql/{version}.sql` | H3: flag name; R1 | `UPDATE ps_feature_flag SET state=1, stability='stable' WHERE name='{domain}'` | if upgrade path |
| **R3** | `migrate-playwright-tests-to-default` | edit all E3–E7 campaign files | H3: flag name; H2: Symfony URLs | Remove `enableFeatureFlag()` from every `beforeAll`; replace legacy URLs with Symfony paths | — |
| **R4** | `add-legacy-deprecation-notice` | `controllers/admin/Admin{Domain}sController.php` (edit) | H2: `admin_{domain}s_index` route; H3: flag name | `$this->warnings[]` banner in `init()`, visible only when flag enabled, links to new page | ~6 mo post-GA |
| **R5** | `write-changelog-deprecation` | `CHANGELOG.md` (edit) | A3: entity name; H1: controller FQCN; R4 | `### Deprecated` entry: class name, migration note, target removal version | with R4 |
| **R6** | `create-removal-issue` | GitHub Issue | R4+R5; A3: hook list | Issue targeting next major: files to delete, files to update, prerequisite checklist | with R4 |

---

## Dependency Graph (Critical Path)

```
A1 ──┐
     ├──► A3 ──► D1 ─┬──► D3 ──► D4, D5, D6, D7
A2 ──┘          D2 ──┘     ↓
                       D8, D9, D10, D11, D12, D13, D14
                                   ↓
                  P1, P2, P3, P4, P5, P6, P7, P8, P9, P10
                                   ↓
                              B1–B6 ✓ (gate: all green)
                      ╔══════════════════════════╗
                      ║     PARALLEL BAND A      ║
               G1,G2,G3,G4,G5     F1,F2,F3,F4,F5,F6
                      ╚══════════════════════════╝
                                   ↓
                      H1 ←─────────────────────
                      H2 (needs H1)
                      H3 (needs H2)
                      ╔══════════════════════════╗
                      ║     PARALLEL BAND B      ║
               JS1,JS2,JS3,JS4,JS5     T1,T2,T3,T4
                      ╚══════════════════════════╝
                                   ↓
                    E1,E2 (early — unblocked after A3)
                    E3,E4,E5,E6,E7 (needs full stack)
                                   ↓
                    R1 → R2+R3 → ... → R4 → R5 → R6
```

**Parallel Band A** (Grid + Form) is the longest parallel opportunity — both streams can progress simultaneously once D1–D14 are finalised.

**E1 + E2** (test fixtures and resetter) can be authored as soon as A3 exists — they depend only on the manifest, not on working code.

---

## Conditional Activation Matrix

| Condition from A3 | Bricks activated |
|---|---|
| Has enum-like fields | D2 |
| Has sub-resources with own table | D7, P2, P8, B5 |
| Has computed/image columns in grid | G3 |
| Has multistore | B6 |
| Has file uploads | D14, P10 |
| Has i18n fields | B4 |
| Has `position` column | G5 (position def), E6 |
| Has dynamic form fields (Vue needed) | F3, JS1, JS2, JS3, JS4, T3 |
| Is a genuinely new feature (not just a migration) | T4 (showcase card) |
| Has upgrade path for existing installs | R2 |

---

## Inter-Skill Communication Contracts

The key artifacts that cross skill boundaries:

| From | To | Contract artifact |
|---|---|---|
| A3 | All groups | `migration-manifest.md` — the single source of truth |
| D1–D14 | P group | Interface FQCNs + namespaces; exception class names; DTO getter list |
| D11 | B3 | `const INVALID_*` codes for constraint violation scenarios |
| D10 | F4 | DTO getter list → `DataProvider::getData()` array keys |
| D3, D4, D7 | F5 | Command constructor + setter signatures → `DataHandler` dispatch |
| G4, G5 | H1 | Grid factory service ID; `{Domain}Filters` class FQCN |
| F6 | H1 | Form builder service ID; form handler service ID |
| H2 | T1, T2 | Route names for `path()` calls |
| H3 | E3–E7 | Feature flag name for `enableFeatureFlag()` |
| F3 | JS2, T3 | Placeholder div ID; `data-*` attribute names; hidden input `name` |
| JS4 | T2 | Webpack bundle asset filename |
| E3–E7 | R1 | Green signal: all campaigns passing |
