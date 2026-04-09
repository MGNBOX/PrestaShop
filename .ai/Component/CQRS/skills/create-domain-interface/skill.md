---
name: create-domain-interface
brick: —
component: CQRS
step: 1
needs: [P1]
produces: "{Domain}RepositoryInterface.php — interface allowing adapter swapping"
conditional: false
---

# create-domain-interface

## Description
Define the repository interface in the Core domain layer. The concrete implementation in Adapter implements this interface. Commands depend on the interface, not the concrete class — enabling adapter replacement.

## Context
- **Brick:** — — Step 1
- **Reads from:** P1 (concrete repository methods to mirror in the interface)
- **Writes to:** P2–P10 (handlers type-hint against this interface, not the concrete class)
- **Artifact:** `src/Core/Domain/{Domain}/{Domain}RepositoryInterface.php`
- **PS example:** `src/Core/Domain/Carrier/` (check for RepositoryInterface)

## Instructions

1. Create interface in `src/Core/Domain/{Domain}/`.
2. Declare all methods needed by command handlers: `get{Domain}`, `create`, `update`, `delete`, plus any sub-resource methods.
3. Use only Core domain types in the signature — no Doctrine/ObjectModel types.

## Rules

- Interface lives in Core, implementation in Adapter
- No Doctrine-specific types in the interface signatures
