---
name: create-money-type-field
brick: —
component: Forms
step: 7
needs: [F1]
produces: "MoneyType field configuration for price/cost fields"
conditional: "only if domain has monetary fields (prices, shipping costs)"
---

# create-money-type-field

## Description
Documents the use of Symfony's MoneyType or PS-specific currency-aware type for price fields. Handles currency display and decimal precision.

## Context
- **Brick:** — — Step 7
- **Reads from:** F1 (form type to edit)
- **Writes to:** F3 (DataHandler converts the decimal value for command setter)
- **Artifact:** Form type (edit)
- **PS example:** Check PS forms with price fields

## Instructions

1. For static currency: use `MoneyType::class` with `'currency' => $defaultCurrencyIsoCode`.
2. For multi-currency: use PS-specific `AmountType` if available, or `NumberType` with currency symbol injected via template.
3. Ensure the field value is stored as a decimal with appropriate precision (typically 6 decimal places in PS).
4. Use `NumberTransformer` or `MoneyTransformer` to convert between form display and DB storage.

## Rules

- Always set explicit decimal scale — PS stores prices with 6 decimal places by default
- Do not use plain `NumberType` for prices without a transformer — floating-point rounding will corrupt values
