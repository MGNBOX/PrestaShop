---
name: create-create-command
brick: D2
component: CQRS
step: 2
needs: [A3]
produces: "Add{Domain}Command.php — write intention carrying all fields for a new entity"
conditional: false
---

# create-create-command

## Description
Create the command that encodes the intention to create a new entity. It carries all fields required for creation, validated at construction time via value objects or primitive validation.

## Context
- **Brick:** D2 — Step 2
- **Reads from:** A3 manifest (form fields tab list → command properties)
- **Writes to:** D9 (Add{Domain}HandlerInterface), P2 (handler implementation), F3 (form data handler dispatches this)
- **Artifact:** `src/Core/Domain/{Domain}/Command/Add{Domain}Command.php`
- **PS example:** `src/Core/Domain/Carrier/Command/AddCarrierCommand.php`

## Instructions

1. Read migration-manifest.md Section 3 (form tabs) to get all creatable fields.
2. Create `src/Core/Domain/{Domain}/Command/Add{Domain}Command.php`.
3. Constructor takes all required fields as typed parameters. Optional fields use nullable types with defaults.
4. Multilingual fields (from A3 manifest Section 3) take `array $localizedValues` keyed by language ID.
5. Validate primitives in constructor (non-empty strings, positive ints, valid enums). Use value objects where a ValueObject exists (D8).
6. Add typed getters for every property — no public properties.
7. Class must be `final`, no interface needed (commands are data objects, not services).

## Rules

- Commands carry data only — no business logic, no DB calls
- All validation failures throw domain exceptions (D7) from the constructor
- Nullable properties represent optional fields that may be omitted on creation
- Sub-resource fields (e.g. zones, ranges) are NOT included here — they get their own command (D14)
