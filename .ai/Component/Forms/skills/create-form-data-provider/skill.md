---
name: create-form-data-provider
brick: F2
component: Forms
step: 7
needs: [D5, D10]
produces: "{Domain}FormDataProvider.php — IdentifiableObject DataProvider that loads entity data for edit form"
conditional: false
---

# create-form-data-provider

## Description
Create the IdentifiableObject DataProvider that populates the edit form with the existing entity's data. Implements `FormDataProviderInterface`, dispatches the Get query, and maps the result to the form's expected array structure.

## Context
- **Brick:** F2 — Step 7
- **Reads from:** D5 (Get{Domain}ForEditing query to dispatch), D10 (query handler return type)
- **Writes to:** H1 (controller calls `$provider->getData($id)` to pre-fill form), F3 (paired DataHandler)
- **Artifact:** `src/Core/Form/IdentifiableObject/DataProvider/{Domain}FormDataProvider.php`
- **PS example:** `src/Core/Form/IdentifiableObject/DataProvider/CarrierFormDataProvider.php`

## Instructions

1. Create class implementing `FormDataProviderInterface`.
2. `getData($id): array` — dispatch `Get{Domain}ForEditing(new {Domain}Id($id))` via query bus.
3. Map the returned DTO/array to the form's expected field structure.
4. `getDefaultData(): array` — return sensible defaults for the create form (empty strings, null IDs, active=true).
5. For multilingual fields, return arrays keyed by language ID.
6. Inject `QueryBus $queryBus` via constructor.

## Rules

- DataProvider maps query result to form data — it does NOT build commands
- getDefaultData() must return the same structure as getData() — form type cannot distinguish
- Multilingual fields must always return an array keyed by int language ID
