---
step: 1
title: "Domain Layer"
previous: step-00-audit.md
next: step-02-adapter-layer.md
deliverable: "src/Core/Domain/{Domain}/ fully populated: ValueObjects, Commands, Queries, DTOs, Exceptions, handler interfaces"
---

# Step 1 — Domain Layer

The domain layer lives in `src/Core/Domain/{Domain}/`. It contains **no implementation** — only contracts, value objects, and data structures. Everything here must be framework-agnostic and dependency-free (no Doctrine, no Symfony, no ObjectModel).

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `create-identity-value-object` | `ValueObject/{Domain}Id.php` | — |
| `create-semantic-value-object` | `ValueObject/{Concept}.php` ×N | if enums |
| `create-add-command` | `Command/Add{Domain}Command.php` | — |
| `create-edit-command` | `Command/Edit{Domain}Command.php` | — |
| `create-delete-commands` | `Command/Delete{Domain}Command.php` + `BulkDelete{Domain}Command.php` | — |
| `create-toggle-commands` | `Command/Toggle{Domain}StatusCommand.php` + bulk variant | — |
| `create-sub-resource-command` | `Command/Set{Domain}{SubRes}Command.php` ×N | if sub-res |
| `create-get-for-editing-query` | `Query/Get{Domain}ForEditing.php` | — |
| `create-list-query` | `Query/Get{Domain}s.php` | — |
| `create-editable-dto` | `QueryResult/Editable{Domain}.php` | — |
| `create-exception-hierarchy` | `Exception/` hierarchy ×N | — |
| `create-command-handler-interfaces` | `CommandHandler/*HandlerInterface.php` ×N | — |
| `create-query-handler-interfaces` | `QueryHandler/*HandlerInterface.php` ×N | — |
| `create-file-uploader-interface` | `{Domain}LogoFileUploaderInterface.php` | if file uploads |

## 1.1 — ValueObjects

### Identity ValueObject

Create `ValueObject/{Domain}Id.php` first. Every other class references it.

```php
// src/Core/Domain/{Domain}/ValueObject/{Domain}Id.php
final class {Domain}Id
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new {Domain}ConstraintException(
                sprintf('Invalid %s ID: %d', '{Domain}', $value),
                {Domain}ConstraintException::INVALID_ID
            );
        }
    }

    public function getValue(): int { return $this->value; }
}
```

### Semantic ValueObjects (one per enum-like concept)

For each field identified in the audit as an enum or constrained set:
- Create `ValueObject/XxxMethod.php` with `const` values and a `fromInt(int): self` named constructor
- The constructor validates the value and throws `{Domain}ConstraintException` with a typed error code
- Example: `ShippingMethod::BY_WEIGHT`, `ShippingMethod::BY_PRICE`
- Do NOT use PHP 8.1 enums — PrestaShop still uses const-based value objects for consistency

### Reference ValueObject (if entity uses a reference ID for versioning)

Some legacy entities (Carrier, Product) use a persistent `id_reference` that survives copy-on-update operations. If the ObjectModel has this field, create `ValueObject/{Domain}ReferenceId.php` with the same pattern as the identity VO.

## 1.2 — Commands

One command per write action from the audit table. Commands carry the intent — they are named in the imperative: `Add`, `Edit`, `Delete`, `Toggle`, `Set`.

### `Add{Domain}Command`

- All required fields as constructor parameters (typed, no optional)
- Optional fields as nullable properties set via setters or constructor with defaults
- Multilingual fields: `array $localizedField` (keyed by language ID as int)
- Association fields: `int[] $groupIds`, `int[] $zoneIds`, etc.
- File upload fields: `?string $uploadedFilePath` (the path to the temp file after upload)
- No business logic — only assignment and basic type safety

```php
final class Add{Domain}Command
{
    // Constructor for required fields
    public function __construct(
        private readonly string $name,
        private readonly bool $active,
        // ...
    ) {}

    // Setters for optional fields
    public function setTrackingUrl(?string $url): self { ... return $this; }
    public function setGroupIds(array $ids): self { ... return $this; }
}
```

### `Edit{Domain}Command`

- First constructor argument: `{Domain}Id $domainId`
- All other fields as nullable setters (only fields that were actually submitted get set — the handler checks `null` vs "not provided")
- This is the "partial update" pattern: `if ($command->getName() !== null) { $entity->name = $command->getName(); }`

### Bulk Commands

`BulkDeleteXxxCommand` and `BulkToggleXxxStatusCommand` take `int[] $domainIds` and validate each in the constructor using `{Domain}Id`.

### Sub-resource Commands

`Set{Domain}{SubResource}Command` — e.g. `SetCarrierRangesCommand`. These always take the parent `{Domain}Id` as first argument, then the full new state of the sub-resource (a collection replaces the previous state atomically).

Never add sub-resource data to `EditXxxCommand` — they are dispatched separately from the form data handler.

## 1.3 — Queries

One query per read use case.

### `Get{Domain}ForEditing`

Used by the form's DataProvider. Takes `{Domain}Id` in constructor. Returns `EditableXxx` DTO (see §1.4).

### `Get{Domain}s` / list query

Used by the grid's QueryBuilder (indirectly — the grid uses DBAL directly, but if a non-grid list is needed, create this query). Takes optional filter parameters.

### Sub-resource Queries

`GetXxx{SubResource}` — e.g. `GetCarrierRanges`. Takes `{Domain}Id`. Returns a typed collection DTO.

## 1.4 — Query Result DTOs

### `QueryResult/Editable{Domain}.php`

The complete data snapshot of an entity for editing. Rules:
- All fields typed and `readonly`
- Constructor injection only — no setters
- `null` for optional fields that may not be set
- Multilingual fields: `array $localizedName` (int → string map)
- Associations: `int[]` for IDs, no nested objects

```php
final class Editable{Domain}
{
    public function __construct(
        private readonly int $domainId,
        private readonly string $name,
        private readonly ?string $trackingUrl,
        private readonly array $localizedDelay, // int[] => string[]
        private readonly int[] $groupIds,
        // ...
    ) {}

    // Getters only
}
```

### Sub-resource DTOs

`CarrierRangesCollection`, `CarrierRangeZone`, etc. — typed collection and item DTOs. Keep them flat and immutable.

## 1.5 — Exceptions

### Hierarchy

```
{Domain}Exception (base, extends DomainException)
├── {Domain}NotFoundException
├── CannotAdd{Domain}Exception
├── CannotUpdate{Domain}Exception
├── CannotDelete{Domain}Exception
├── Cannot{Action}{Domain}Exception  (one per toggle/bulk action)
└── {Domain}ConstraintException      (validation failures)
```

### `{Domain}ConstraintException`

This is the most important exception. It uses typed `const` error codes so callers can react differently:

```php
final class {Domain}ConstraintException extends {Domain}Exception
{
    public const INVALID_ID = 1;
    public const INVALID_NAME = 2;
    public const INVALID_TRACKING_URL = 3;
    public const INVALID_GROUP_ID = 4;
    // one const per constraint that can fail
}
```

These codes are used in Behat assertions (`the constraint error code should be 2`).

## 1.6 — Handler Interfaces

For every Command and Query, create a handler interface in `CommandHandler/` or `QueryHandler/`:

```php
// src/Core/Domain/{Domain}/CommandHandler/Add{Domain}HandlerInterface.php
interface Add{Domain}HandlerInterface
{
    public function handle(Add{Domain}Command $command): {Domain}Id;
}

// src/Core/Domain/{Domain}/QueryHandler/Get{Domain}ForEditingHandlerInterface.php
interface Get{Domain}ForEditingHandlerInterface
{
    public function handle(Get{Domain}ForEditing $query): Editable{Domain};
}
```

Interfaces have no implementation — they live in Core, implementations live in Adapter.

## 1.7 — File uploader interface (if needed)

If the entity has a logo or file upload:

```php
// src/Core/Domain/{Domain}/{Domain}LogoFileUploaderInterface.php
interface {Domain}LogoFileUploaderInterface
{
    public function upload(string $sourcePath, int $domainId): void;
    public function delete(int $domainId): void;
}
```

The concrete implementation lives in `src/Adapter/File/Uploader/`.

## Checklist

- [ ] `ValueObject/{Domain}Id.php` created with positive-int guard
- [ ] Semantic ValueObjects created for each enum-like field
- [ ] `Add{Domain}Command` created with all required fields
- [ ] `Edit{Domain}Command` created with nullable setters for all fields
- [ ] `Delete{Domain}Command` and `BulkDelete{Domain}Command` created
- [ ] Toggle commands created
- [ ] Sub-resource commands created (one per sub-resource, never merged into Edit)
- [ ] `Get{Domain}ForEditing` query created
- [ ] Sub-resource queries created
- [ ] `QueryResult/Editable{Domain}.php` DTO created, all fields typed and readonly
- [ ] Exception hierarchy created with `{Domain}ConstraintException` + typed const codes
- [ ] Handler interfaces created in `CommandHandler/` and `QueryHandler/`
- [ ] File uploader interface created if needed
