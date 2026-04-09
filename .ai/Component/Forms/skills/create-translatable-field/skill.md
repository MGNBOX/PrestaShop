---
name: create-translatable-field
brick: F5
component: Forms
step: 7
needs: [F1, A2]
produces: "TranslatableType field configuration for multilingual form fields"
conditional: "only if domain has multilingual fields (A2 lang=true fields)"
---

# create-translatable-field

## Description
Documents how to implement multilingual form fields using PrestaShop's `TranslatableType`. Multilingual fields render a separate input per language tab and submit a language-keyed array.

## Context
- **Brick:** F5 — Step 7
- **Reads from:** F1 (form type to edit), A2 (fields where lang=true)
- **Writes to:** F2/F3 (DataProvider/DataHandler handle the lang-keyed arrays)
- **Artifact:** Form type files (edit F1 output)
- **PS example:** Check TranslatableType usage in any PS form with multilingual name/description

## Instructions

1. Add multilingual field:
   ```php
   ->add('name', TranslatableType::class, [
       'type' => TextType::class,
       'options' => ['constraints' => [new NotBlank()]],
   ])
   ```
2. `TranslatableType` renders one input per active shop language.
3. Submitted data is `['name' => [1 => 'English name', 2 => 'French name']]`.
4. In F2/F3, map to command's `setLocalizedNames()` setter.
5. For textarea (description): use `TranslatableType` wrapping `TextareaType`.

## Rules

- TranslatableType field name must match the command's `setLocalized{Field}()` setter
- The form data structure (lang-keyed array) must match the DataProvider's output structure
