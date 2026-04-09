---
step: 3
title: "Behat Integration Tests"
skill: legacy-to-symfony-migration
previous: step-02-adapter-layer.md
next: step-04-grid.md
deliverable: "tests/Integration/Behaviour/ fully green for all domain commands and queries"
---

# Step 3 — Behat Integration Tests

Integration tests are written **immediately after the Adapter layer**, before building any UI. They give confidence that the CQRS layer is correct before the controller and form layers are built on top of it. A failing Behat suite is a blocker — do not proceed to Step 4 with red tests.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **B1** | `create-behat-feature-context` | `Context/Domain/{Domain}/{Domain}FeatureContext.php` | — |
| **B2** | `write-behat-crud-scenarios` | `Scenario/{Domain}/{domain}_management.feature` | — |
| **B3** | `write-behat-constraint-scenarios` | append to `{domain}_management.feature` | — |
| **B4** | `write-behat-i18n-scenarios` | append to `{domain}_management.feature` | if i18n |
| **B5** | `write-behat-sub-resource-scenarios` | `Scenario/{Domain}/{domain}_{subres}.feature` ×N | if sub-res |
| **B6** | `write-behat-multistore-scenarios` | `Scenario/{Domain}/{domain}_multishop.feature` | if multistore |

## 3.1 — Directory structure

```
tests/Integration/Behaviour/Features/
├── Context/Domain/{Domain}/
│   ├── {Domain}FeatureContext.php          # main context
│   ├── {Domain}RangesFeatureContext.php    # sub-resource context (if any)
│   └── {Domain}MultishopFeatureContext.php # multistore context (if any)
└── Scenario/{Domain}/
    ├── {domain}_management.feature         # CRUD + toggles
    ├── {domain}_ranges.feature             # sub-resource scenarios
    ├── {domain}_multishop.feature          # multistore scenarios
    └── {domain}_tax_rule_group.feature     # if applicable
```

## 3.2 — Feature context class

The feature context class holds the step definitions and the in-memory state shared between steps.

```php
// tests/Integration/Behaviour/Features/Context/Domain/{Domain}/{Domain}FeatureContext.php
class {Domain}FeatureContext extends AbstractDomainFeatureContext
{
    // Stores entity IDs created during the scenario
    private array $createdEntities = [];

    /**
     * @When I add a {domain} :reference with following properties:
     */
    public function iAddA{Domain}WithProperties(string $reference, TableNode $table): void
    {
        $data = $table->getRowsHash();
        $command = new Add{Domain}Command(
            name: $data['name'],
            active: isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : true,
            // map all other fields
        );

        if (isset($data['tracking_url'])) {
            $command->setTrackingUrl($data['tracking_url']);
        }

        $id = $this->getCommandBus()->handle($command);
        $this->createdEntities[$reference] = $id->getValue();
    }

    /**
     * @Then {domain} :reference should have following properties:
     */
    public function {domain}ShouldHaveProperties(string $reference, TableNode $table): void
    {
        $id = new {Domain}Id($this->createdEntities[$reference]);
        $editable = $this->getQueryBus()->handle(new Get{Domain}ForEditing($id));

        $data = $table->getRowsHash();
        Assert::assertSame($data['name'], $editable->getName());
        Assert::assertSame(
            filter_var($data['active'], FILTER_VALIDATE_BOOLEAN),
            $editable->isActive()
        );
        // assert every field
    }
}
```

Key patterns:
- Use string `$reference` keys (e.g. `"carrier1"`) to track created entities across steps — never hardcode IDs
- The `AbstractDomainFeatureContext` provides `getCommandBus()` and `getQueryBus()` — never construct handlers directly
- Use `TableNode $table` with `$table->getRowsHash()` for field-value pairs; use `$table->getColumnsHash()` for collections
- Assert with `PHPUnit\Framework\Assert` — not `assert()` or Behat's built-in assert

## 3.3 — Scenario coverage for the main feature file

The `{domain}_management.feature` must cover:

### Basic CRUD

```gherkin
Scenario: Add a {domain} with minimum required fields
  When I add a {domain} "d1" with following properties:
    | name   | Test |
    | active | true |
  Then {domain} "d1" should have following properties:
    | name   | Test |
    | active | true |

Scenario: Edit a {domain}
  Given I add a {domain} "d1" with following properties:
    | name | Original |
  When I edit {domain} "d1" with following properties:
    | name | Updated |
  Then {domain} "d1" should have following properties:
    | name | Updated |

Scenario: Delete a {domain}
  Given I add a {domain} "d1" with following properties:
    | name | ToDelete |
  When I delete {domain} "d1"
  Then {domain} "d1" should not exist

Scenario: Toggle {domain} status
  Given I add a {domain} "d1" with following properties:
    | name   | Test |
    | active | true |
  When I toggle status of {domain} "d1"
  Then {domain} "d1" should have following properties:
    | active | false |
```

### Constraint violations

```gherkin
Scenario: Cannot add a {domain} with empty name
  When I add a {domain} with invalid name ""
  Then I should get an error that {domain} name is invalid

Scenario: Cannot get a {domain} with invalid ID
  When I get {domain} with id "-1"
  Then I should get a {domain} constraint error with code 1
```

### Multilingual fields (if applicable)

```gherkin
Scenario: Add a {domain} with localized fields
  When I add a {domain} "d1" with following localizations:
    | language | delay    |
    | en       | 2-3 days |
    | fr       | 2-3 jours |
  Then {domain} "d1" should have delay "2-3 days" for language "en"
  And {domain} "d1" should have delay "2-3 jours" for language "fr"
```

## 3.4 — Sub-resource feature file

For complex sub-resources (ranges, zones, prices):

```gherkin
# {domain}_ranges.feature
Scenario: Set ranges for a {domain}
  Given I add a {domain} "d1" with following properties:
    | name | Carrier with ranges |
  When I set ranges for {domain} "d1":
    | from | to   | zone  | price |
    | 0    | 10   | EU    | 5.00  |
    | 10   | 50   | EU    | 10.00 |
  Then {domain} "d1" should have 2 ranges
  And {domain} "d1" range 1 should go from "0" to "10" with price "5.00" for zone "EU"

Scenario: Replacing ranges atomically
  Given {domain} "d1" has 3 ranges
  When I set ranges for {domain} "d1":
    | from | to  | zone | price |
    | 0    | 100 | EU   | 20.00 |
  Then {domain} "d1" should have 1 range
```

## 3.5 — Multistore feature file

```gherkin
# {domain}_multishop.feature
Background:
  Given multiple shops are configured
  And shop "shop1" exists
  And shop "shop2" exists

Scenario: Add a {domain} for all shops
  When I add a {domain} "d1" in all shops with following properties:
    | name   | Global |
    | active | true   |
  Then {domain} "d1" should exist in shop "shop1"
  And {domain} "d1" should exist in shop "shop2"

Scenario: Edit {domain} only in one shop
  When I edit {domain} "d1" in shop "shop1" with following properties:
    | name | Shop1 name |
  Then {domain} "d1" in shop "shop1" should have name "Shop1 name"
  And {domain} "d1" in shop "shop2" should have name "Global"
```

## 3.6 — Running tests

```bash
# Run all scenarios for the domain
php vendor/bin/behat --suite=domain tests/Integration/Behaviour/Features/Scenario/{Domain}/

# Run a specific feature
php vendor/bin/behat tests/Integration/Behaviour/Features/Scenario/{Domain}/{domain}_management.feature

# Run in verbose mode to see step output
php vendor/bin/behat -v tests/Integration/Behaviour/Features/Scenario/{Domain}/
```

All scenarios must be green before proceeding to Step 4. A failing Behat test means a bug in the Adapter layer, not in the test — fix the handler, not the test.

## Checklist

- [ ] `{Domain}FeatureContext.php` created with step defs for Add, Edit, Delete, Toggle, Get
- [ ] `{domain}_management.feature` covers: add minimum, add all fields, edit, delete, toggle, bulk toggle
- [ ] `{domain}_management.feature` covers constraint violations for every typed const error code
- [ ] Multilingual field scenarios added if applicable
- [ ] Sub-resource feature file created with atomic replace scenario
- [ ] Multistore feature file created with per-shop isolation scenario
- [ ] All scenarios passing locally (`php vendor/bin/behat ...`)
