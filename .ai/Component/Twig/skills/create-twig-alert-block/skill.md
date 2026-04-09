---
name: create-twig-alert-block
brick: "—"
component: Twig
step: 9
needs: [T1, T2]
produces: "Flash message display block in index and form templates"
conditional: false
---

# create-twig-alert-block

## Description
Documents how to correctly render Symfony flash messages (success/error/warning) in PS admin templates using the standard alert block. Every index and form template needs this.

## Context
- **Brick:** — — Step 9
- **Reads from:** T1, T2 (templates being edited)
- **Writes to:** T1, T2 (adds flash block to each template's content block)
- **Artifact:** Template blocks (edit T1, T2 output)
- **PS example:** Any PS admin template with flash message rendering

## Instructions

1. In the `{% block content %}` of each template, include the flash block:
   `{% include '@PrestaShop/Admin/Common/flash_messages.html.twig' %}`
2. Flash messages are added in the controller via `$this->addFlash('success', $this->trans('...'))`.
3. Types: `success` (green), `error` (red), `warning` (yellow), `info` (blue).
4. Use PS translation domain: `Admin.Notifications.Success`, `Admin.Notifications.Error`.

## Rules

- Flash messages must be translatable — never hardcode English strings
- Include the flash block BEFORE the main content — not after
