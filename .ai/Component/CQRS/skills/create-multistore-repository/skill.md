---
name: create-multistore-repository
brick: —
component: CQRS
step: 2
needs: [P1]
produces: "Pattern for implementing multistore-aware writes using getShopIdsByConstraint()"
conditional: false
---

# create-multistore-repository

## Description
Documents the required multistore pattern in every PrestaShop repository. The `getShopIdsByConstraint()` method from `AbstractMultiShopObjectModelRepository` must be called on every write to resolve which shop contexts to apply the change to.

## Context
- **Brick:** — — Step 2
- **Reads from:** P1 (repository to extend with this pattern)
- **Writes to:** All handler skills (P2–P10) that call write methods on the repository
- **Artifact:** pattern document / reference (no new file — this skill augments P1)
- **PS example:** `src/Adapter/Carrier/Repository/CarrierRepository.php`

## Instructions

1. Inject or receive `ShopConstraint` in write methods.
2. Call `$shopIds = $this->getShopIdsByConstraint($shopConstraint)` at the start of every write method.
3. Iterate `$shopIds` and apply the write for each shop context.
4. For all-shops mode: `ShopConstraint::allShops()`.
5. For single-shop: `ShopConstraint::shop($shopId)`.
6. For shop-group: `ShopConstraint::shopGroup($groupId)`.
7. Never hard-code `Context::getContext()->shop->id` in repositories.

## Rules

- getShopIdsByConstraint() is never optional — call it on every write
- Do not read Context directly in repositories — receive ShopConstraint as parameter
- Single-shop installs still go through this path (returns an array with one ID)
