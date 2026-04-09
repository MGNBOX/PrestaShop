---
name: create-partial-update-pattern
brick: —
component: CQRS
step: 1
needs: [D3]
produces: "Reference implementation of the nullable-setter partial update pattern used in all Edit commands"
conditional: false
---

# create-partial-update-pattern

## Description
Documents the nullable setter pattern used in all `Edit{Domain}Command` classes in PrestaShop. Every editable field is `?Type = null`; handlers check nullability before updating each field.

## Context
- **Brick:** — — Step 1
- **Reads from:** D3 (edit command fields and types)
- **Writes to:** P3 (edit handler uses this pattern)
- **Artifact:** pattern document / reference (no new file — this skill explains the pattern)
- **PS example:** `src/Core/Domain/Carrier/Command/EditCarrierCommand.php`

## Instructions

1. In `Edit{Domain}Command`, declare all fields as `private ?Type $field = null`.
2. Add fluent setter: `public function setField(Type $value): self { $this->field = $value; return $this; }`.
3. Add nullable getter: `public function getField(): ?Type { return $this->field; }`.
4. In the handler, check before updating: `if ($command->getField() !== null) { $entity->field = $command->getField(); }`.
5. This allows the form data handler (F3) to only set fields visible in the current request.

## Rules

- Never initialize optional fields to non-null defaults in constructor
- Null means "not set in this request" — not "set to null in DB"
- Required fields (like the entity ID) go in the constructor, not as setters
