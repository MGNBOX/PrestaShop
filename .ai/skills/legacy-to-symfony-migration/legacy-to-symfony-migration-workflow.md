# PrestaShop Legacy → Symfony/CQRS Migration Workflow

> Derived from the Carrier page migration case study (PRs #20737, #36063–#37638)
> cross-referenced with PrestaShop architecture best practices.

---

## Carrier Page Migration: PR Timeline

| Phase | PR | Title | Author | Date | Technical Focus |
|---|---|---|---|---|---|
| 0 – Pre-work | #14551 | AddCarrierCommand (abandoned) | zuk3975 | 2019–2021 | CQRS spike — never merged |
| 0 – Pre-work | #14397 | Wizard-form rendering (abandoned) | zuk3975 | 2019–2021 | 5-step wizard — superseded by tab design |
| 1 – Listing | #20737 | Migrate carriers listing | ks129 | 2021-03-17 | Grid + 5 CQRS commands (delete, toggle) + routing + Twig + JS |
| 2 – CQRS foundation | #36063 | CQRS for Add/Get/Update/UploadLogo | boherm | 2024-05-22 | AddCarrierCommand, EditCarrierCommand, GetCarrierForEditing + adapters + Behat |
| 3 – First form render | #36271 | Basic general form | jolelievre | 2024-06-04 | CarrierController (PrestaShopAdminController), CarrierType, GeneralSettings, DataProvider, DataHandler, routing, Twig, TS entry point |
| 4 – Field expansion | #36246 | Add more fields | tleon | 2024-06-11 | Dimensional constraints (max w/h/d/weight), `is_free`, `shipping_handling` |
| 4 – Field expansion | #36300 | Shipping locations & costs (CQRS) | boherm | 2024-06-11 | ShippingMethod + OutOfRangeBehavior value objects, zone/cost fields in commands |
| 5 – More tabs | #36381 | Size/weight tab + group access | boherm | 2024-06-19 | SizeWeightSettings form, customer group field |
| 6 – Ranges CQRS | #36380 | Get/Set Carrier Ranges | boherm | 2024-06-25 | SetCarrierRangesCommand, GetCarrierRanges, CarrierRangeRepository, Behat |
| 6 – Ranges CQRS | #36387 | Tax rule + multistore | tleon | 2024-06-28 | SetCarrierTaxRuleGroupCommand, AbstractMultiShopObjectModelRepository, multistore Behat |
| 7 – Optimise | #36434 | Edit carrier optimisation | boherm | 2024-07-01 | Skip unnecessary DB writes on unchanged fields |
| 7 – Feature flag | #36706 | Clean legacy links pt.5 | jolelievre | 2024-08-16 | `feature_flag.xml` entry `stability="beta"`, legacy link cleanup |
| 8 – Complex UI | #36534 | Ranges selector component v1 | boherm | 2024-07-15 | `CarrierRangesModal.vue`, `CarrierRangesType.php` |
| 8 – Complex UI | #36537 | Shipping costs & locations tab | tleon | 2024-07-17 | ShippingLocationsAndCostsType, MultipleZoneChoiceType, TaxGroupChoiceType, Vue integration |
| 8 – Complex UI | #36655 | Ranges UI part 2 | boherm | 2024-08-20 | carrier-form-manager.ts, CostsRangeType, CostsZoneType, final form theme |
| 9 – UI tests | #36112–#36954 | Playwright test suites | Progi1984 | 2024-05–09 | 7 distinct campaigns (CRUD, bulk, position, form tabs) |
| 10 – Refactor | #36818 | Dissociate zones from ranges | Nakahiru | 2024-09-12 | Architectural fix: zone selection ≠ range definition in CQRS + Vue |
| 11 – Bug fixes | #36876, #36892, #37053, #37271, #37297 | Various fixes | multiple | 2024-09–11 | Logo upload error, tab redirect on validation, required fields, negative ranges guard |
| 12 – GA | #37638 | Feature flag → stable | jolelievre | 2025-02-07 | `stability="stable"`, `state="1"`, full Playwright migration |
| 13 – Deprecation | #39050 | Migration banner in legacy ctrl | Hlavtox | 2025-07-09 | Legacy controller shows "please migrate" notice |

---

## PS-Specific Quirks Not in Standard Docs

| Quirk | Where it appears |
|---|---|
| `AbstractMultiShopObjectModelRepository` must be the base of every repository | `CarrierRepository`, `CarrierRangeRepository` |
| Sub-resources (ranges, tax groups) get their own dedicated Command, not merged into Edit | `SetCarrierRangesCommand`, `SetCarrierTaxRuleGroupCommand` |
| `NavigationTabType` for multi-tab forms (not Symfony's `TabPane`) | `CarrierType` |
| `IdentifiableObject` DataProvider + DataHandler replaces form event subscribers | `CarrierFormDataProvider`, `CarrierFormDataHandler` |
| File uploaders are a domain interface, implemented in Adapter | `CarrierLogoFileUploaderInterface` |
| Listing and form migrations are separate milestones, often years apart | PR #20737 (2021) vs #36063 (2024) |
| Feature flag is not optional — it is the routing mechanism and GA gate | `_legacy_feature_flag: carrier` on all routes |
| Vue components are needed whenever a form field is too dynamic for Symfony alone | `CarrierRangesModal.vue` |
| Legacy controller is never deleted — only gets a deprecation banner | PR #39050 |
| `stability="beta"` → `"stable"` is a formal step requiring its own PR | PR #37638 |

---

## Definitive Migration Checklist

### Phase 0 — Audit

- [ ] Read the legacy controller (`AdminXxxController.php`) and all `ObjectModel` classes it touches
- [ ] List every action: create, edit, delete, bulk-delete, toggle fields, reorder, search/filter
- [ ] List all data fields and group them by complexity: simple scalars, associations (groups, shops, zones), file uploads, nested sub-resources (ranges, prices, etc.)
- [ ] Decide if listing and form are done in **one sprint or two separate milestones**

---

### Phase 1 — Domain Layer (`src/Core/Domain/{Domain}/`)

- [ ] Create `ValueObject/{Domain}Id.php` (validate positive int)
- [ ] Create one `ValueObject/` per enum-like concept (e.g. `ShippingMethod`, `OutOfRangeBehavior`)
- [ ] Create Commands for **all write actions** found in the audit:
  - `Add{Domain}Command`
  - `Edit{Domain}Command`
  - `Delete{Domain}Command`, `BulkDelete{Domain}Command`
  - `Toggle{Domain}StatusCommand`, `BulkToggle{Domain}StatusCommand`
  - **One dedicated command per sub-resource** (e.g. `SetCarrierRangesCommand`, `SetCarrierTaxRuleGroupCommand`)
- [ ] Create Query handler interfaces for **all read actions**:
  - `Get{Domain}ForEditing` → returns `EditableXxx` DTO
  - `Get{Domain}s` / filtered list query (used by grid)
- [ ] Create `QueryResult/Editable{Domain}.php` — immutable DTO with all fields typed
- [ ] Create `Exception/{Domain}Exception.php` (base), `{Domain}ConstraintException.php` (with `const` error codes), and specific `CannotAdd/Update/Delete` exceptions
- [ ] Create `{Domain}CommandHandlerInterface` and `{Domain}QueryHandlerInterface` for each command/query

---

### Phase 2 — Adapter Layer (`src/Adapter/{Domain}/`)

- [ ] Create `Repository/{Domain}Repository.php` extending `AbstractMultiShopObjectModelRepository`:
  - `get(DomainId): ObjectModel`
  - `add(ObjectModel, $shopIds): DomainId`
  - `update(ObjectModel): void`
  - `getShopIdsByConstraint()` used in every write
- [ ] Create sub-resource repositories if needed (e.g. `{Domain}RangeRepository`)
- [ ] Create `Validate/{Domain}Validator.php` with all business-rule guards
- [ ] Implement all Command handlers in `CommandHandler/` using `#[AsCommandHandler]` attribute
- [ ] Implement all Query handlers in `QueryHandler/` using `#[AsQueryHandler]` attribute
- [ ] If file uploads: create `File/Uploader/{Domain}LogoFileUploader.php` implementing domain interface

---

### Phase 3 — Behat Integration Tests (`tests/Integration/Behaviour/`)

- [ ] Create `Features/Context/Domain/{Domain}/{Domain}FeatureContext.php`
- [ ] Create `Features/Scenario/{Domain}/{domain}_management.feature` covering:
  - Add with minimum fields
  - Add with all fields
  - Edit
  - Delete
  - Toggle status
  - Constraint violation scenarios (negative ID, invalid values, etc.)
- [ ] Create separate feature files for sub-resources: `{domain}_ranges.feature`, `{domain}_multishop.feature`, `{domain}_tax_rule_group.feature`
- [ ] Run Behat suite before proceeding to UI layer

---

### Phase 4 — Grid (`src/Core/Grid/`)

- [ ] Create `Definition/Factory/{Domain}GridDefinitionFactory.php`:
  - Columns: ID, name, logo/image (if any), status toggle, position (if reorderable), row actions
  - Bulk actions: delete, enable, disable
  - Filters matching column IDs
- [ ] Create `Query/{Domain}QueryBuilder.php` (DBAL) — select all grid columns, apply filter conditions
- [ ] Create `Data/Factory/{Domain}GridDataFactory.php` if computed columns need post-processing (e.g. image URLs)
- [ ] Create `Search/Filters/{Domain}Filters.php`
- [ ] If reorderable: add `PositionDefinition` service

---

### Phase 5 — Symfony Controller (`src/PrestaShopBundle/Controller/Admin/`)

- [ ] Create controller extending `PrestaShopAdminController`
- [ ] Implement actions with `#[AdminSecurity]` and `#[DemoRestricted]` PHP attributes:
  - `indexAction()` — renders grid; passes `GridFactoryInterface` result to Twig
  - `searchAction()` — handles grid filter POST
  - `createAction()` — GET renders empty form; POST dispatches Add command
  - `editAction({domainId})` — GET renders populated form; POST dispatches Edit command
  - `deleteAction({domainId})` — dispatches Delete command
  - `bulkDeleteAction()` — dispatches BulkDelete command
  - `toggleStatusAction({domainId})` — dispatches Toggle command
  - `updatePositionAction()` — dispatches position update (if reorderable)

---

### Phase 6 — Routing (`src/PrestaShopBundle/Resources/config/routing/admin/`)

- [ ] Create or extend the YAML routing file for this section
- [ ] Add all routes with `_legacy_feature_flag: {domain}` on every route
- [ ] Confirm legacy routes have `_legacy_link` fallback pointing to `AdminXxxController`
- [ ] Register the routing file in the parent `routing.yml`

---

### Phase 7 — Form (`src/PrestaShopBundle/Form/Admin/`)

- [ ] Create `{Domain}/{Domain}Type.php` as the root form using `NavigationTabType` for tabs
- [ ] Create one sub-form per logical tab (e.g. `GeneralSettings.php`, `ShippingLocationsAndCostsType.php`, `SizeWeightSettings.php`)
- [ ] For complex dynamic fields (multi-row tables, range editors): create a `Type/` subdirectory with dedicated form types; bridge to Vue via a PHP form type + data attribute
- [ ] Create `Core/Form/IdentifiableObject/DataProvider/{Domain}FormDataProvider.php`:
  - `getData(int $id): array` — dispatches `Get{Domain}ForEditing` query, maps DTO to form array
  - `getDefaultData(): array` — returns defaults for new entity form
- [ ] Create `Core/Form/IdentifiableObject/DataHandler/{Domain}FormDataHandler.php`:
  - `create(array $data): int` — dispatches Add command, returns new ID
  - `update(int $id, array $data): void` — dispatches Edit command + any sub-resource commands
- [ ] Register all services in DI YAML (`form_type.yml`, `form_handler.yml`, `form_data_provider.yml`)

---

### Phase 8 — Frontend (if complex dynamic UI needed)

- [ ] Create `admin-dev/themes/new-theme/js/pages/{domain}/form/` directory
- [ ] Create TS entry point `index.ts` wiring form manager
- [ ] For complex fields (range tables, dynamic rows): create a `.vue` SFC in `components/`
- [ ] Register entry point in webpack config

---

### Phase 9 — Twig Templates (`src/PrestaShopBundle/Resources/views/Admin/`)

- [ ] Create `index.html.twig` extending the standard PS admin layout — render grid component, pass alerts, showcase card if first-time page
- [ ] Create `form.html.twig` — render form with tabs, handle create/edit title switching
- [ ] Create `FormTheme/` overrides if custom widget rendering is needed for complex form types

---

### Phase 10 — Feature Flag Registration

- [ ] Add entry in `install-dev/data/xml/feature_flag.xml`:
  ```xml
  <feature_flag id="{domain}" stability="beta" state="0" />
  ```
- [ ] Set `state="0"` (disabled) for initial merge; enable manually in dev for testing

---

### Phase 11 — Playwright UI Tests (`tests/UI/campaigns/functional/BO/`)

- [ ] Write CRUD campaign: `01_CRUD{Domain}.ts` — create with all fields, verify, edit, delete
- [ ] Write bulk actions campaign: enable, disable, bulk delete
- [ ] Write one campaign **per form tab** testing all fields in that tab
- [ ] Write quick-edit / position change campaigns if applicable
- [ ] Run against `feature_flag.xml` with flag enabled

---

### Phase 12 — General Availability

- [ ] After QA sign-off, update `feature_flag.xml`: `stability="stable"`, `state="1"`
- [ ] Migrate all Playwright tests to use new routes by default
- [ ] Verify legacy controller still loads (do NOT delete it)

---

### Phase 13 — Legacy Deprecation (deferred, ~6–12 months after GA)

- [ ] Add deprecation notice/banner to `AdminXxxController.php` pointing to new URL
- [ ] Schedule removal of legacy controller in a future major release
