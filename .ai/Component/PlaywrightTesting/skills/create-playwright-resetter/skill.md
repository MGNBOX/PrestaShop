---
name: create-playwright-resetter
brick: E2
component: PlaywrightTesting
step: 11
needs: [A3]
produces: "tests/Resources/Resetter/{Domain}Resetter.php — PHP cleanup class for test isolation"
conditional: false
---

# create-playwright-resetter

## Description
Create the PHP Resetter class that cleans up entities created during test campaigns. Called in `afterAll` hooks to ensure test isolation between campaigns.

## Context
- **Brick:** E2 — Step 11
- **Reads from:** A3 (table names, sub-resource tables to clean)
- **Writes to:** E3–E7 (all campaigns call the resetter in afterAll)
- **Artifact:** `tests/Resources/Resetter/{Domain}Resetter.php`
- **PS example:** `tests/Resources/Resetter/CarrierResetter.php`

## Instructions

1. Create `{Domain}Resetter.php` in `tests/Resources/Resetter/`.
2. Implement a static method: `public static function resetAll(): void`.
3. Delete test-created entities by name pattern: `DELETE FROM ps_{domain} WHERE name LIKE 'Test %'`.
4. Also clean sub-resource tables (if any): `DELETE FROM ps_{sub} WHERE id_{domain} NOT IN (SELECT id_{domain} FROM ps_{domain})`.
5. Reset the auto-increment if needed (optional).
6. Import and call in campaign `afterAll`: `{Domain}Resetter::resetAll()`.

## Rules

- Only delete entities matching a test-specific name pattern — never truncate production data
- Clean sub-resource tables before the parent table (foreign key order)
- Resetter is idempotent — calling it twice must be safe
