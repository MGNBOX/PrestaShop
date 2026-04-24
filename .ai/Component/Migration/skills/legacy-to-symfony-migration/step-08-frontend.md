---
step: 8
title: "Frontend (TypeScript + Vue)"
previous: step-07-form.md
next: step-09-twig-templates.md
deliverable: "admin-dev/themes/new-theme/js/pages/{domain}/ with TS entry points and Vue components for any dynamic form fields"
---

# Step 8 — Frontend

Most migrated pages require minimal frontend work — standard Symfony form fields render with existing PS JavaScript infrastructure. This step is **only needed** when the page has:

- Dynamic multi-row tables (e.g. shipping ranges per zone)
- Interactive components that can't be expressed as static Symfony form fields
- Custom list interactions beyond the standard PS grid (bulk selects, drag-and-drop beyond `PositionColumn`)

If none of these apply, skip this step and go directly to Step 9.

## Micro-Skills


| Skill | Artifact | ⚠ |
|---|---|---|
| `create-form-entry-point` | `js/pages/{domain}/form/index.ts` | if Vue |
| `create-form-manager` | `js/pages/{domain}/form/{domain}-form-manager.ts` | if Vue |
| `create-vue-component` | `js/pages/{domain}/form/components/{Domain}{Field}.vue` ×N | if Vue |
| `register-webpack-entry` | webpack config (edit) | if Vue |
| `implement-tab-error-navigation` | append to `js/pages/{domain}/form/index.ts` | **always** |

## 8.1 — Directory structure

```
admin-dev/themes/new-theme/js/pages/{domain}/
├── index.ts                    # Listing page entry point (if needed)
└── form/
    ├── index.ts                # Form page entry point
    ├── {domain}-form-manager.ts  # Orchestrates all form interactions
    └── components/
        └── {Domain}XxxModal.vue  # Complex interactive components
```

For simple forms, `form/index.ts` can be a direct call to a form manager without sub-components.

## 8.2 — Entry point (`form/index.ts`)

The entry point is the file imported by webpack and injected into the Twig template. It initialises all interactive components after DOM ready.

```typescript
// admin-dev/themes/new-theme/js/pages/{domain}/form/index.ts
import {Domain}FormManager from './{domain}-form-manager';

const manager = new {Domain}FormManager();
manager.init();

export default manager;
```

## 8.3 — Form manager

The form manager is the orchestration class. It:
- Reads initial data from `data-` attributes on the form element (injected by Twig)
- Mounts Vue components into their placeholder `<div>` elements
- Wires form submission to collect Vue component data back into hidden fields

```typescript
// {domain}-form-manager.ts
import {createApp} from 'vue';
import {Domain}RangesModal from './components/{Domain}RangesModal.vue';

export default class {Domain}FormManager {
  private app: ReturnType<typeof createApp> | null = null;

  init(): void {
    const rangesContainer = document.querySelector('#js-carrier-ranges');
    if (!rangesContainer) return;

    // Read initial data passed from PHP via data attribute
    const initialRanges = JSON.parse(
      rangesContainer.getAttribute('data-ranges') ?? '[]'
    );
    const zones = JSON.parse(
      rangesContainer.getAttribute('data-zones') ?? '[]'
    );

    this.app = createApp({Domain}RangesModal, {
      initialRanges,
      zones,
      onUpdate: (ranges: unknown[]) => this.syncToHiddenField(ranges),
    });
    this.app.mount(rangesContainer);
  }

  private syncToHiddenField(data: unknown[]): void {
    const input = document.querySelector<HTMLInputElement>(
      'input[name="{domain}[shipping][ranges]"]'
    );
    if (input) {
      input.value = JSON.stringify(data);
    }
  }
}
```

## 8.4 — Vue SFC for complex fields

Vue components are used for fields that require:
- Adding/removing rows dynamically (range tables)
- In-place editing within a modal
- Cross-field interactions (e.g. changing zone selection updates cost table columns)

```vue
<!-- components/{Domain}RangesModal.vue -->
<template>
  <div class="carrier-ranges">
    <table v-if="rows.length > 0">
      <thead>
        <tr>
          <th>From</th>
          <th>To</th>
          <th v-for="zone in zones" :key="zone.id">{{ zone.name }}</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="index">
          <td><input v-model="row.from" type="number" min="0" /></td>
          <td><input v-model="row.to" type="number" min="0" /></td>
          <td v-for="zone in zones" :key="zone.id">
            <input v-model="row.costs[zone.id]" type="number" min="0" step="0.01" />
          </td>
          <td>
            <button type="button" @click="removeRow(index)">Remove</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button type="button" @click="addRow">Add range</button>
  </div>
</template>

<script lang="ts">
import {defineComponent, ref, watch, PropType} from 'vue';

interface Zone { id: number; name: string; }
interface RangeRow { from: number; to: number; costs: Record<number, number>; }

export default defineComponent({
  name: '{Domain}RangesModal',
  props: {
    initialRanges: { type: Array as PropType<RangeRow[]>, default: () => [] },
    zones: { type: Array as PropType<Zone[]>, required: true },
    onUpdate: { type: Function as PropType<(rows: RangeRow[]) => void>, required: true },
  },
  setup(props) {
    const rows = ref<RangeRow[]>([...props.initialRanges]);

    const addRow = () => rows.value.push({ from: 0, to: 0, costs: {} });
    const removeRow = (i: number) => rows.value.splice(i, 1);

    // Sync back to hidden form field on every change
    watch(rows, (val) => props.onUpdate(val), { deep: true });

    return { rows, addRow, removeRow };
  },
});
</script>
```

## 8.5 — PHP form type bridge

The Vue component needs a PHP-side form type to:
1. Render the placeholder `<div>` with `data-` attributes carrying initial JSON
2. Receive the JSON string back from the hidden field on submission

```php
// Form/Admin/{Section}/{Subsection}/{Domain}/Type/{Domain}RangesType.php
final class {Domain}RangesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ranges_data', HiddenType::class);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Pass initial data to the template for the Vue component
        $view->vars['ranges'] = $options['ranges'] ?? [];
        $view->vars['zones'] = $options['zones'] ?? [];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'ranges' => [],
            'zones' => [],
        ]);
    }
}
```

The Twig template for this type (in `FormTheme/`) renders the `<div>` placeholder with the data attributes, then the hidden input for the serialised value.

## 8.6 — Webpack registration

Add the entry point to webpack configuration:

```javascript
// admin-dev/themes/new-theme/webpack.config.js (or equivalent config)
// In the `entry` object:
'{domain}-form': path.resolve(__dirname, 'js/pages/{domain}/form/index.ts'),
'{domain}-index': path.resolve(__dirname, 'js/pages/{domain}/index.ts'),
```

Then reference in the Twig template (Step 9) via:

```twig
{% block javascripts %}
  {{ parent() }}
  <script src="{{ asset('themes/new-theme/public/{domain}-form.bundle.js') }}"></script>
{% endblock %}
```

## 8.7 — Tab error navigation

When a form is submitted with validation errors, the user should be taken to the tab containing the first error — not left on the currently visible tab with no visible errors.

```typescript
// In the form manager init():
document.addEventListener('DOMContentLoaded', () => {
  const firstError = document.querySelector('.has-error, .is-invalid');
  if (firstError) {
    // Find the tab containing this field and activate it
    const tabPane = firstError.closest('.tab-pane');
    if (tabPane) {
      const tabId = tabPane.id;
      const tabTrigger = document.querySelector(`[data-target="#${tabId}"], [href="#${tabId}"]`);
      (tabTrigger as HTMLElement)?.click();
    }
  }
});
```

This was a real bug in the Carrier migration (PR #36892) — include it from the start.

## Checklist

- [ ] Assessed whether Vue components are needed (skip step if not)
- [ ] `form/index.ts` entry point created
- [ ] `{domain}-form-manager.ts` created, reads initial data from `data-` attributes
- [ ] Vue SFC created for each dynamic field component
- [ ] Vue components sync data back to hidden form fields on change
- [ ] PHP bridge form type created for each Vue-backed field
- [ ] Webpack entry point registered
- [ ] Tab error navigation implemented (jump to first-error tab on validation failure)
- [ ] `index.ts` listing entry point created if listing page has JS interactions
