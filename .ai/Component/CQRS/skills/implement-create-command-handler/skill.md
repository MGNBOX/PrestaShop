---
name: implement-create-command-handler
brick: P2
component: CQRS
step: 2
needs: [D2, D9, P1]
produces: "Add{Domain}Handler.php — concrete handler for entity creation"
conditional: false
---

# implement-create-command-handler

## Description
Implement the add handler that reads from the Add{Domain}Command and delegates to the repository for persistence. Returns the new {Domain}Id.

## Context
- **Brick:** P2 — Step 2
- **Reads from:** D2 (command structure), D9 (interface to implement), P1 (repository to call)
- **Writes to:** F3 (form data handler dispatches this), H1 (controller calls via bus)
- **Artifact:** `src/Adapter/{Domain}/CommandHandler/Add{Domain}Handler.php`
- **PS example:** `src/Adapter/Carrier/CommandHandler/AddCarrierHandler.php`

## Instructions

1. Create `Add{Domain}Handler.php` implementing `Add{Domain}HandlerInterface`.
2. Constructor injects `{Domain}Repository $repository`.
3. `handle(Add{Domain}Command $command): {Domain}Id` method body:
   a. Construct the ObjectModel or Doctrine entity from command data.
   b. For multilingual fields, map `$command->getLocalizedNames()` to the lang array.
   c. Call `$this->repository->create(...)`.
   d. Return the new `{Domain}Id`.
4. Catch persistence exceptions and rethrow as domain exceptions (D7).
5. Never call another handler — compose at controller level.

## Rules

- Handlers contain business orchestration only — no SQL, no ObjectModel directly
- All DB access goes through the repository (P1)
- Never call another handler from within a handler
- Return {Domain}Id from Add handler, void from others
