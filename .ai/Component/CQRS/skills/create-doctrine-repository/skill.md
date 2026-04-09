---
name: create-doctrine-repository
brick: P1
component: CQRS
step: 1
needs: [D1, A2, A3]
produces: "{Domain}Repository.php extending AbstractMultiShopObjectModelRepository — the single persistence entry point"
conditional: false
---

# create-doctrine-repository

## Description
Create the Doctrine-based repository that extends `AbstractMultiShopObjectModelRepository`. This is the ONLY class that touches the database — all handlers delegate to it. Multistore support is never optional.

## Context
- **Brick:** P1 — Step 1
- **Reads from:** A2 (table name, column list), D1 ({Domain}Id type), A3 (sub-resources)
- **Writes to:** P2–P10 (all handlers inject and call this repository)
- **Artifact:** `src/Adapter/{Domain}/{Domain}Repository.php`
- **PS example:** `src/Adapter/Carrier/Repository/CarrierRepository.php`

## Instructions

1. Create `src/Adapter/{Domain}/{Domain}Repository.php`.
2. Extend `AbstractMultiShopObjectModelRepository` (namespace: `PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint`).
3. Inject `ShopConstraint` as a constructor parameter (or receive it per-method for multistore writes).
4. Implement `get{Domain}({Domain}Id $id): {LegacyObjectModel}` — loads the ObjectModel, throws `{Domain}NotFoundException` if not found.
5. Implement `create({Domain}): {Domain}Id` — calls ObjectModel `save()` or `add()`, wraps in try/catch, returns new ID.
6. Implement `update({Domain})` — calls ObjectModel `save()` or `update()`.
7. Implement `delete({Domain}Id $id, ShopConstraint $shopConstraint)` — calls `getShopIdsByConstraint()` for multistore-aware deletion.
8. For each sub-resource (A3 Section 6), implement `set{SubResource}s({Domain}Id, array $items)` using atomic replace.
9. The `getShopIdsByConstraint()` call is REQUIRED on every write in multistore mode — never skip it.

## Rules

- Every write method must call `getShopIdsByConstraint()` when multistore is active
- Never use `Db::getInstance()` — use Doctrine DBAL or ObjectModel methods
- Sub-resource writes always use delete-all + insert-all (atomic replace), never partial
- Throw typed domain exceptions (D7), not generic exceptions
