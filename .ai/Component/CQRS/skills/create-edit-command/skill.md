---
name: create-edit-command
brick: D3
component: CQRS
step: 3
needs: [D1, A3]
produces: "Edit{Domain}Command.php — partial-update command with nullable setters"
conditional: false
---

# create-edit-command

## Description
Create the partial-update command using the nullable setter pattern. The constructor takes only the `{Domain}Id`; every other field is set via optional setters, allowing the form handler to set only the fields that changed.

## Context
- **Brick:** D3 — Step 3
- **Reads from:** D1 ({Domain}Id type), A3 manifest (all editable fields)
- **Writes to:** D9 (Edit{Domain}HandlerInterface), P3, F3
- **Artifact:** `src/Core/Domain/{Domain}/Command/Edit{Domain}Command.php`
- **PS example:** `src/Core/Domain/Carrier/Command/EditCarrierCommand.php`

## Instructions

1. Constructor takes only `{Domain}Id $carrierId`.
2. Declare every editable field as `private ?Type $field = null`.
3. Add a fluent setter for each field: `public function setName(string $name): self { $this->name = $name; return $this; }`.
4. Add a getter that returns the nullable type: `public function getName(): ?string { return $this->name; }`.
5. In the handler (P3), check `if ($command->getName() !== null)` before updating — this is the partial-update pattern.
6. Multilingual fields use `?array` with `setLocalizedNames(array $names): self`.
7. Do NOT include fields that can never be edited after creation (e.g. immutable identifiers).

## Rules

- Constructor ONLY takes {Domain}Id — never field values
- Every setter returns `self` (fluent interface)
- Handler must check nullability before updating — never assume all fields are set
- Sub-resource fields stay in their own command (D14), not here
