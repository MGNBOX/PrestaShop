---
step: 9
title: "Twig Templates"
previous: step-08-frontend.md
next: step-10-feature-flag.md
deliverable: "src/PrestaShopBundle/Resources/views/Admin/{Section}/{Subsection}/{Domain}s/ with index.html.twig and form.html.twig"
---

# Step 9 — Twig Templates

Two templates are required for every migrated page: the listing (`index.html.twig`) and the add/edit form (`form.html.twig`). Templates extend the standard PrestaShop admin layout and use the PS component macros. Keep logic out of templates — pass everything needed from the controller.

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `create-index-template` | `views/Admin/{Section}/{Domain}s/index.html.twig` | — |
| `create-form-template` | `views/Admin/{Section}/{Domain}s/form.html.twig` | — |
| `create-form-theme-widget` | `views/.../FormTheme/{widget}.html.twig` ×N | if Vue |
| `create-showcase-card` | `views/.../Blocks/showcase_card.html.twig` | if new feature |

## 9.1 — Directory structure

```
src/PrestaShopBundle/Resources/views/Admin/{Section}/{Subsection}/{Domain}s/
├── index.html.twig
├── form.html.twig
├── Blocks/
│   ├── showcase_card.html.twig   # First-time feature introduction card (optional)
│   └── information_block.html.twig  # Contextual info block (optional)
└── FormTheme/
    └── {domain}_ranges.html.twig  # Custom widget rendering for complex form types (if needed)
```

## 9.2 — `index.html.twig` (listing page)

```twig
{# src/PrestaShopBundle/Resources/views/Admin/{Section}/{Subsection}/{Domain}s/index.html.twig #}
{% extends '@PrestaShop/Admin/layout.html.twig' %}

{% block title %}{{ 'Carriers'|trans({}, 'Admin.{Section}.Feature') }}{% endblock %}

{% block content_header_title %}
  {{ '{Domain}s'|trans({}, 'Admin.{Section}.Feature') }}
{% endblock %}

{% block content_header_toolbar %}
  <a
    href="{{ path('admin_{domain}s_create') }}"
    class="btn btn-primary"
  >
    <i class="material-icons">add_circle_outline</i>
    {{ 'Add new {domain}'|trans({}, 'Admin.Actions') }}
  </a>
{% endblock %}

{% block content %}
  {# Showcase card — shown on first visit, dismissed via cookie #}
  {% if showcaseCardName is defined %}
    {% include '@PrestaShop/Admin/{Section}/{Subsection}/{Domain}s/Blocks/showcase_card.html.twig' %}
  {% endif %}

  {# Grid component #}
  {{ include('@PrestaShop/Admin/Common/Grid/grid_panel.html.twig', {
    'grid': {domain}Grid,
  }) }}
{% endblock %}

{% block javascripts %}
  {{ parent() }}
  {# Only include if the listing page has custom JS #}
{% endblock %}
```

Key variables passed by the controller (Step 5 `indexAction()`):
- `{domain}Grid` — the presented grid (result of `$this->presentGrid($grid)`)
- `showcaseCardName` — string ID of the showcase card cookie (omit to hide the card)
- `enableSidebar` — `true` to show the right sidebar

## 9.3 — `form.html.twig` (add/edit form)

```twig
{# src/PrestaShopBundle/Resources/views/Admin/{Section}/{Subsection}/{Domain}s/form.html.twig #}
{% extends '@PrestaShop/Admin/layout.html.twig' %}

{% block title %}
  {% if isEdit %}
    {{ 'Edit %name%'|trans({'%name%': layoutTitle}, 'Admin.{Section}.Feature') }}
  {% else %}
    {{ 'Add new {domain}'|trans({}, 'Admin.Actions') }}
  {% endif %}
{% endblock %}

{% block content_header_title %}
  {{ layoutTitle }}
{% endblock %}

{% block content_header_toolbar %}
  <a href="{{ path('admin_{domain}s_index') }}" class="btn btn-outline-secondary">
    <i class="material-icons">arrow_back</i>
    {{ 'Back to list'|trans({}, 'Admin.Actions') }}
  </a>
{% endblock %}

{% block content %}
  {{ form_start({domain}Form, {
    'action': isEdit
      ? path('admin_{domain}s_edit', { '{domain}Id': {domain}Id })
      : path('admin_{domain}s_create'),
    'attr': {
      'class': 'form-horizontal',
      'novalidate': '',
      'enctype': 'multipart/form-data',  {# only if file uploads exist #}
    },
  }) }}

    {# Tab navigation rendered by NavigationTabType #}
    {{ form_widget({domain}Form) }}

    <div class="card-footer">
      <div class="d-flex justify-content-end">
        <a href="{{ path('admin_{domain}s_index') }}" class="btn btn-outline-secondary mr-1">
          {{ 'Cancel'|trans({}, 'Admin.Actions') }}
        </a>
        <button type="submit" class="btn btn-primary">
          {{ 'Save'|trans({}, 'Admin.Actions') }}
        </button>
      </div>
    </div>

  {{ form_end({domain}Form) }}
{% endblock %}

{% block javascripts %}
  {{ parent() }}
  {# Inject the compiled TS bundle for this form page #}
  <script src="{{ asset('themes/new-theme/public/{domain}-form.bundle.js') }}"></script>
{% endblock %}
```

Notes:
- `enctype="multipart/form-data"` is required whenever the form has a file upload field — omitting it silently breaks file uploads
- `novalidate` disables native HTML5 browser validation in favour of server-side validation
- The `form_widget({domain}Form)` call renders the entire `NavigationTabType` tree, including tab headers and panes

## 9.4 — `FormTheme/` widget overrides

When a PHP form type needs custom HTML rendering (e.g. the Vue component placeholder), create a form theme template:

```twig
{# FormTheme/{domain}_ranges.html.twig #}
{% block {domain}_ranges_type_widget %}
  {# Placeholder div for the Vue component — data- attributes carry initial JSON #}
  <div
    id="js-{domain}-ranges"
    data-ranges="{{ form.vars.ranges|json_encode }}"
    data-zones="{{ form.vars.zones|json_encode }}"
  ></div>

  {# Hidden input that Vue writes back to on change #}
  {{ form_widget(form.ranges_data) }}
{% endblock %}
```

Register the form theme in the main `form.html.twig` or globally in `config.yml`:

```twig
{% form_theme {domain}Form
  '@PrestaShop/Admin/{Section}/{Subsection}/{Domain}s/FormTheme/{domain}_ranges.html.twig'
%}
```

## 9.5 — Showcase card (optional)

For pages that are genuinely new features (not just migrations), a showcase card introduces the feature on first visit:

```twig
{# Blocks/showcase_card.html.twig #}
{% set showcaseCard = {
  'title': '{Domain}s'|trans({}, 'Admin.{Section}.Feature'),
  'description': 'Manage your shipping {domain}s from here.'|trans({}, 'Admin.{Section}.Feature'),
  'links': [
    {
      'url': 'https://docs.prestashop.com/...',
      'label': 'Documentation'|trans({}, 'Admin.Global'),
    }
  ]
} %}
{{ include('@PrestaShop/Admin/Common/Layout/Components/showcase_card.html.twig', {card: showcaseCard}) }}
```

The card is dismissed via a cookie whose name matches `showcaseCardName` passed from the controller.

## 9.6 — Translation domains

All strings in PS admin templates use the `Admin.*` translation domain pattern:
- `Admin.Global` — universal strings: Save, Cancel, Active, ID, Name, Actions
- `Admin.Actions` — action labels: Add new, Edit, Delete, Back to list, Search
- `Admin.Notifications.Success/Error/Warning` — flash message content
- `Admin.{Section}.Feature` — section-specific strings (e.g. `Admin.Shipping.Feature`, `Admin.Catalog.Feature`)

Never use free-form domain strings — use the correct `Admin.*` domain so strings can be translated by the community.

## Checklist

- [ ] Directory created under the correct `Admin/{Section}/{Subsection}/` path
- [ ] `index.html.twig` extends `@PrestaShop/Admin/layout.html.twig`
- [ ] `index.html.twig` renders grid via `grid_panel.html.twig`
- [ ] `index.html.twig` has "Add new" button linking to create route
- [ ] `form.html.twig` uses `form_start` with correct action (create vs edit), `novalidate`, `enctype` if files
- [ ] `form.html.twig` renders `NavigationTabType` via `form_widget`
- [ ] `form.html.twig` has Save and Cancel buttons
- [ ] `form.html.twig` includes the compiled JS bundle if frontend work was done (Step 8)
- [ ] `FormTheme/` template created for each Vue-backed form type
- [ ] Form theme registered with `form_theme` tag in the template
- [ ] All trans() calls use correct `Admin.*` translation domains
