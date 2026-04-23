---
step: 2
title: "Adapter Layer"
previous: step-01-domain-layer.md
next: step-03-behat-tests.md
deliverable: "src/Adapter/{Domain}/ with Repository, Validator, all Command and Query handlers — all Behat-green"
---

# Step 2 — Adapter Layer

The adapter layer lives in `src/Adapter/{Domain}/`. It bridges Core domain contracts with the legacy ObjectModel layer. Handlers here implement the interfaces defined in Step 1 and are tagged for the Symfony service bus via PHP attributes.

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `create-domain-repository` | `Repository/{Domain}Repository.php` | — |
| `create-sub-resource-repository` | `Repository/{Domain}{SubRes}Repository.php` ×N | if sub-res |
| `create-domain-validator` | `Validate/{Domain}Validator.php` | — |
| `create-add-command-handler` | `CommandHandler/Add{Domain}Handler.php` | — |
| `create-edit-command-handler` | `CommandHandler/Edit{Domain}Handler.php` | — |
| `create-delete-command-handlers` | `CommandHandler/Delete{Domain}Handler.php` + `BulkDelete{Domain}Handler.php` | — |
| `create-toggle-command-handlers` | `CommandHandler/Toggle{Domain}StatusHandler.php` + bulk variant ×N | — |
| `create-sub-resource-command-handler` | `CommandHandler/Set{Domain}{SubRes}Handler.php` ×N | if sub-res |
| `create-get-for-editing-handler` | `QueryHandler/Get{Domain}ForEditingHandler.php` | — |
| `create-file-uploader-implementation` | `File/Uploader/{Domain}LogoFileUploader.php` | if file uploads |

## 2.1 — Repository

### `Repository/{Domain}Repository.php`

This is the central data access class. It **must** extend `AbstractMultiShopObjectModelRepository` — not `AbstractObjectModelRepository` and not a custom base. Multistore awareness is mandatory even if multistore is not in scope for the first sprint.

```php
// src/Adapter/{Domain}/Repository/{Domain}Repository.php
final class {Domain}Repository extends AbstractMultiShopObjectModelRepository
{
    public function get({Domain}Id $id): {LegacyObjectModel}
    {
        /** @var {LegacyObjectModel} $entity */
        $entity = $this->getObjectModel(
            $id->getValue(),
            {LegacyObjectModel}::class,
            {Domain}NotFoundException::class
        );
        return $entity;
    }

    public function add(
        {LegacyObjectModel} $entity,
        ShopConstraint $shopConstraint
    ): {Domain}Id {
        $this->addObjectModel($entity, CannotAdd{Domain}Exception::class);
        $shopIds = $this->getShopIdsByConstraint($shopConstraint);
        $this->updateObjectModelShopAssociations(
            $entity->id,
            {LegacyObjectModel}::class,
            $shopIds
        );
        return new {Domain}Id((int) $entity->id);
    }

    public function update(
        {LegacyObjectModel} $entity,
        ShopConstraint $shopConstraint
    ): void {
        $shopIds = $this->getShopIdsByConstraint($shopConstraint);
        $this->updateObjectModelForShops(
            $entity,
            $shopIds,
            CannotUpdate{Domain}Exception::class
        );
    }

    public function delete({Domain}Id $id): void
    {
        $entity = $this->get($id);
        $this->deleteObjectModel($entity, CannotDelete{Domain}Exception::class);
    }
}
```

Key rules:
- `getShopIdsByConstraint(ShopConstraint $constraint): array` is called in **every write** — it resolves which shops the operation affects (all shops, one shop, or a shop group)
- `$entity->id` is cast to `int` before wrapping in the identity VO — ObjectModel sets it as a mixed/string after `save()`
- The `ShopConstraint` is injected via the command — do not read it from the legacy `Context` singleton

### Sub-resource repositories

If the domain has sub-resources with their own table (e.g. `ps_carrier_range_price`), create `Repository/{Domain}{SubResource}Repository.php`. It may extend `AbstractObjectModelRepository` (not the multistore variant) if the sub-resource is not shop-scoped.

Sub-resource repositories expose: `findByParent({Domain}Id): array`, `save(array $records, {Domain}Id $parentId): void` (atomic replace), `deleteByParent({Domain}Id): void`.

## 2.2 — Validator

### `Validate/{Domain}Validator.php`

Validates the ObjectModel state before persisting. Called from handlers after building the ObjectModel from command data.

```php
final class {Domain}Validator
{
    public function validate({LegacyObjectModel} $entity): void
    {
        $errors = $entity->validateFields(false, true);
        if ($errors !== true) {
            throw new {Domain}ConstraintException(
                sprintf('Invalid %s fields: %s', '{Domain}', implode(', ', $errors)),
                {Domain}ConstraintException::INVALID_FIELDS
            );
        }
    }

    public function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new {Domain}ConstraintException(
                'Name cannot be empty',
                {Domain}ConstraintException::INVALID_NAME
            );
        }
    }
}
```

Rule: keep validation in the Validator, not scattered across handlers. Handlers call `$this->validator->validate($entity)` before any `$repository->add()` or `$repository->update()`.

## 2.3 — Command Handlers

Each handler implements one handler interface from Core and is tagged with `#[AsCommandHandler]`.

### `CommandHandler/Add{Domain}Handler.php`

```php
#[AsCommandHandler]
final class Add{Domain}Handler implements Add{Domain}HandlerInterface
{
    public function __construct(
        private readonly {Domain}Repository $repository,
        private readonly {Domain}Validator $validator,
        // ShopContext or ShopConstraint resolver if needed
    ) {}

    public function handle(Add{Domain}Command $command): {Domain}Id
    {
        $entity = new {LegacyObjectModel}();
        $this->fillEntityFromCommand($entity, $command);
        $this->validator->validate($entity);

        $shopConstraint = ShopConstraint::allShops(); // or from Context
        $domainId = $this->repository->add($entity, $shopConstraint);

        // Handle associations after save (groups, zones)
        if ($command->getGroupIds() !== null) {
            $entity->setGroups($command->getGroupIds());
        }

        // Handle sub-resources (dispatch to sub-resource handlers or inline)
        // Note: logo upload handled separately via the uploader interface

        return $domainId;
    }

    private function fillEntityFromCommand(
        {LegacyObjectModel} $entity,
        Add{Domain}Command $command
    ): void {
        $entity->name = $command->getName();
        $entity->active = $command->isActive();
        // all scalar fields mapped 1:1
        // multilingual: $entity->delay = $command->getLocalizedDelay();
    }
}
```

### `CommandHandler/Edit{Domain}Handler.php`

The edit handler uses the "only update what was provided" pattern. Every nullable setter on the command is checked:

```php
$entity = $this->repository->get($command->getId());

if ($command->getName() !== null) {
    $entity->name = $command->getName();
}
if ($command->isActive() !== null) {
    $entity->active = $command->isActive();
}
// ... etc for every field

$this->validator->validate($entity);
$this->repository->update($entity, $shopConstraint);
```

This avoids unintentionally overwriting fields that were not included in the form submission.

### Delete / Bulk / Toggle handlers

These are thin: get the entity, call the repository method, handle exceptions. Example:

```php
#[AsCommandHandler]
final class Delete{Domain}Handler implements Delete{Domain}HandlerInterface
{
    public function handle(Delete{Domain}Command $command): void
    {
        $entity = $this->repository->get($command->getId());
        if ($entity->deleted) {
            throw new {Domain}NotFoundException(...);
        }
        $this->repository->delete($command->getId());
    }
}
```

### Sub-resource command handlers

`Set{Domain}{SubResource}Handler` always performs an **atomic replace**:
1. Delete all existing sub-resource rows for the parent ID
2. Insert all new rows from the command

Never partial-update sub-resources — the full collection state is always passed and stored wholesale.

## 2.4 — Query Handlers

### `QueryHandler/Get{Domain}ForEditingHandler.php`

```php
#[AsQueryHandler]
final class Get{Domain}ForEditingHandler implements Get{Domain}ForEditingHandlerInterface
{
    public function handle(Get{Domain}ForEditing $query): Editable{Domain}
    {
        $entity = $this->repository->get($query->getId());

        return new Editable{Domain}(
            domainId: $entity->id,
            name: $entity->name,
            localizedDelay: $entity->delay, // already a lang-keyed array from ObjectModel
            trackingUrl: $entity->url ?: null,
            active: (bool) $entity->active,
            groupIds: array_map('intval', $entity->getAssociatedGroupIds()),
            // ...
        );
    }
}
```

Rules:
- Cast all ObjectModel properties to their proper types — ObjectModel returns everything as strings from DB
- Use `array_map('intval', ...)` for ID arrays
- Use `(bool)` for booleans
- Return `null` for truly optional fields, not empty strings

### Sub-resource query handlers

`Get{Domain}{SubResource}Handler` loads the sub-resource table rows and maps them to typed DTOs.

## 2.5 — File uploader (if needed)

```php
// src/Adapter/File/Uploader/{Domain}LogoFileUploader.php
final class {Domain}LogoFileUploader implements {Domain}LogoFileUploaderInterface
{
    public function upload(string $sourcePath, int $domainId): void
    {
        // validate mime type
        // generate target path using _PS_IMG_DIR_ or equivalent
        // move uploaded file
        // generate thumbnails if required
        // if any step fails: throw {Domain}LogoUploadFailedException
    }
}
```

Upload errors must throw the domain exception, not PHP errors or generic exceptions.

## 2.6 — Service registration

All adapters are auto-wired if `services.yaml` has autoconfigure/autowire enabled for the `Adapter` namespace. However, handlers must be explicitly tagged if not using auto-tagging:

```yaml
# services/adapter/carrier/command_handlers.yml
PrestaShop\PrestaShop\Adapter\{Domain}\CommandHandler\Add{Domain}Handler:
    tags:
        - { name: tactician.handler, command: '...Add{Domain}Command' }
```

With `#[AsCommandHandler]` the attribute handles this automatically — verify the attribute is supported in the PS version being targeted.

## Checklist

- [ ] `Repository/{Domain}Repository.php` extends `AbstractMultiShopObjectModelRepository`
- [ ] `get()`, `add()`, `update()`, `delete()` implemented with correct exception types
- [ ] `getShopIdsByConstraint()` called in every write operation
- [ ] Sub-resource repositories created if needed (atomic replace pattern)
- [ ] `Validate/{Domain}Validator.php` created with all field validations
- [ ] `Add{Domain}Handler` implemented — fills entity, validates, saves, handles associations
- [ ] `Edit{Domain}Handler` implemented — null-checks every nullable field before assigning
- [ ] Delete, bulk delete, toggle handlers implemented
- [ ] Sub-resource command handlers implemented (atomic replace)
- [ ] `Get{Domain}ForEditingHandler` implemented — all ObjectModel properties cast to typed PHP
- [ ] Sub-resource query handlers implemented
- [ ] File uploader implemented if needed, throws domain exception on failure
- [ ] All handlers auto-wired or explicitly tagged in services YAML
