# Playwright Component

## Purpose

Browser-based UI acceptance tests for the back-office and front-office. Tests simulate real user interactions (navigation, form filling, clicking) and validate the DOM. Complements Behat (which tests the CQRS layer without a browser).

## Stack

- **Playwright** — browser automation
- **Mocha** — test framework (describe/it blocks, before/after hooks)
- **Chai** — assertion library (`expect`)
- **Faker** — test data generation (via `@prestashop-core/ui-testing`)
- **TypeScript** — all test files are `.ts`

## Layers

| Layer | Location | Description |
|-------|----------|-------------|
| Campaigns | `tests/UI/campaigns/` | Test files organized by type (functional, sanity, regression) |
| Common tests | `tests/UI/commonTests/` | Reusable describe blocks (shared setup/teardown) |
| Test data | `tests/UI/data/` | XML fixtures + local data files |
| Utils | `tests/UI/utils/` | testContext, setup, browser helpers |
| Configuration | `tests/UI/.mocharc.json`, `tsconfig.json`, `.env*` | Test runner and environment config |

## External library: `@prestashop-core/ui-testing`

Most page objects, Faker data classes, and utilities live in the external **[ui-testing-library](https://github.com/PrestaShop/ui-testing-library)**, not in the core repo.

Imported as: `@prestashop-core/ui-testing`

What it provides:
- **Page objects:** BO pages (`boTaxesPage`, `boTaxesCreatePage`, `boDashboardPage`, `boLoginPage`...), FO pages (`foHomePage`...)
- **Faker data:** `FakerTax`, `FakerProduct`, `FakerImageType`... — generate randomized test entities
- **Predefined data:** `dataTaxes`, `dataTaxOptions`... — reference to demo install fixtures
- **Utilities:** `utilsPlaywright` (browser lifecycle), `utilsFile`, `utilsCore`
- **Types:** `Page`, `BrowserContext`, `Browser`

When creating tests for a new migrated page, you typically need to create new page objects and Faker classes **in the ui-testing-library first**, then write campaigns in the core repo that import them.

## Campaign directory structure

```
tests/UI/campaigns/
├── functional/
│   ├── BO/
│   │   ├── 00_login/
│   │   ├── 02_orders/
│   │   │   ├── 01_orders/
│   │   │   └── 02_invoices/
│   │   ├── 03_catalog/
│   │   ├── 09_shipping/
│   │   ├── 11_international/
│   │   │   └── 03_taxes/
│   │   │       ├── 01_taxes/
│   │   │       │   ├── 01_filterTaxes.ts
│   │   │       │   ├── 02_CRUDTaxesInBO.ts
│   │   │       │   └── 04_taxOptionsForm.ts
│   │   │       └── 02_taxRules/
│   │   └── ...
│   ├── FO/
│   └── API/
├── sanity/
└── modules/
```

**Naming convention:** directories use `XX_descriptiveName` numbering. Files use `XX_descriptiveName.ts`.

## Page Object Model (POM) pattern

Page objects encapsulate page interactions. They:
- **Never assert** — they return values (strings, booleans, numbers) for the test to assert
- **Follow naming:** `bo{Feature}Page` for listing, `bo{Feature}CreatePage` for form
- **Use selector naming:** `{name}{Type}` camelCase (e.g. `submitMainFormButton`, `nameInput`, `activeToggle`)
- **Live in the ui-testing-library**, not in the core repo

## Test file structure

```typescript
import testContext from '@utils/testContext';
import {expect} from 'chai';
import {
  boDashboardPage, boLoginPage, boTaxesPage, boTaxesCreatePage,
  type BrowserContext, FakerTax, type Page, utilsPlaywright,
} from '@prestashop-core/ui-testing';

const baseContext: string = 'functional_BO_international_taxes_taxes_CRUDTaxesInBO';

describe('BO - International - Taxes : CRUD Tax', async () => {
  let browserContext: BrowserContext;
  let page: Page;

  before(async function () {
    browserContext = await utilsPlaywright.createBrowserContext(this.browser);
    page = await utilsPlaywright.newTab(browserContext);
  });

  after(async () => {
    await utilsPlaywright.closeBrowserContext(browserContext);
  });

  it('should login in BO', async function () {
    await testContext.addContextItem(this, 'testIdentifier', 'loginBO', baseContext);
    await boLoginPage.goTo(page, global.BO.URL);
    await boLoginPage.successLogin(page, global.BO.EMAIL, global.BO.PASSWD);
    // ...
  });
});
```

## Key conventions

- **testIdentifier:** every `it()` step must call `testContext.addContextItem(this, 'testIdentifier', 'uniqueId', baseContext)` — enables test result tracking. Must be globally unique across all campaigns
- **baseContext:** string combining campaign path segments, e.g. `'functional_BO_international_taxes_taxes_CRUDTaxesInBO'`
- **Use `function()` not arrow functions** in `describe()` blocks — Mocha needs `this` context
- **Clean up after tests:** `afterAll` must leave the system in its pre-test state. Delete created entities, revert settings
- **Feature flag:** for pages still in beta, enable the flag in `before()` and it will be removed at GA
- **Common tests:** reusable setup/teardown functions live in `tests/UI/commonTests/`. Import and call them to avoid duplication (e.g. `createProductTest`, `deleteProductTest`)
- **No random data in assertions:** use deterministic Faker values or predefined data for assertions

## Canonical examples

- `tests/UI/campaigns/functional/BO/11_international/03_taxes/01_taxes/02_CRUDTaxesInBO.ts` — simple CRUD campaign
- `tests/UI/campaigns/functional/BO/11_international/03_taxes/01_taxes/01_filterTaxes.ts` — filter campaign

## Related

- [Grid Component](../Grid/CONTEXT.md) — grid definition determines filter/sort test coverage
- [Controller Component](../Controller/CONTEXT.md) — routes tested by campaigns
