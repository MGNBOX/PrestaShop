---
step: 7
title: "Form"
skill: legacy-to-symfony-migration
previous: step-06-routing.md
next: step-08-frontend.md
deliverable: "src/PrestaShopBundle/Form/Admin/ types + Core/Form/IdentifiableObject/ DataProvider and DataHandler, all wired in DI YAML"
---

# Step 7 — Form

PrestaShop uses the **IdentifiableObject** form pattern, which differs from standard Symfony form handling. Understanding it is mandatory before writing a single form type.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **F1** | `create-root-form-type` | `Form/Admin/{Section}/{Domain}/{Domain}Type.php` | — |
| **F2** | `create-tab-form-type` | `Form/Admin/{Section}/{Domain}/{Tab}SettingsType.php` ×N | — |
| **F3** | `create-complex-form-subtype` | `Form/Admin/{Section}/{Domain}/Type/{Domain}{Field}Type.php` ×N | if Vue |
| **F4** | `create-form-data-provider` | `Core/Form/IdentifiableObject/DataProvider/{Domain}FormDataProvider.php` | — |
| **F5** | `create-form-data-handler` | `Core/Form/IdentifiableObject/DataHandler/{Domain}FormDataHandler.php` | — |
| **F6** | `register-form-services` | `services/bundle/form/` YAML entries | — |

## 7.1 — The IdentifiableObject pattern

Standard Symfony forms bind to an entity object. PrestaShop forms bind to a **plain PHP array**. The separation is:

- `{Domain}FormDataProvider` — translates a domain entity (via QueryBus) into the form's initial data array
- `{Domain}FormDataHandler` — translates the submitted form data array into Commands and dispatches them
- `{Domain}FormBuilder` — the Symfony form builder configured with the form type and data provider
- `{Domain}FormHandler` — the Symfony form handler that calls the data handler on valid submission

The form type (`{Domain}Type`) knows nothing about the domain. It only describes the HTML structure.

## 7.2 — Root form type

`src/PrestaShopBundle/Form/Admin/{Section}/{Subsection}/{Domain}/{Domain}Type.php`

Uses `NavigationTabType` as the root structure — **not** `TabType` or standard Symfony tabs.

```php
final class {Domain}Type extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('general', GeneralSettingsType::class, [
                'label' => $this->trans('General settings', [], 'Admin.{Section}.Feature'),
            ])
            ->add('shipping', ShippingLocationsAndCostsType::class, [
                'label' => $this->trans('Shipping locations and costs', [], 'Admin.{Section}.Feature'),
            ])
            ->add('sizes', SizeWeightSettingsType::class, [
                'label' => $this->trans('Size, weight, and group access', [], 'Admin.{Section}.Feature'),
            ]);
    }

    public function getParent(): string
    {
        return NavigationTabType::class;
    }
}
```

`NavigationTabType` renders a tabbed layout matching the PS back-office design system. Each `add()` call is one tab.

## 7.3 — Sub-form types (one per tab)

### `GeneralSettingsType.php`

Contains all fields shown in the "General" tab: name, active toggle, logo upload, tracking URL, grade, transit time.

```php
final class GeneralSettingsType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->trans('Name', [], 'Admin.Global'),
                'required' => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('active', SwitchType::class, [
                'label' => $this->trans('Active', [], 'Admin.Global'),
                'required' => false,
            ])
            ->add('logo', FileType::class, [
                'label' => $this->trans('Logo', [], 'Admin.Global'),
                'required' => false,
                'constraints' => [
                    new File(['mimeTypes' => ['image/png', 'image/jpeg', 'image/gif']]),
                ],
            ])
            ->add('delay', TranslatableType::class, [
                // PS-specific type for multilingual string fields
                'label' => $this->trans('Transit time', [], 'Admin.{Section}.Feature'),
                'type' => TextType::class,
                'required' => false,
            ])
            ->add('grade', IntegerType::class, [
                'label' => $this->trans('Speed grade', [], 'Admin.{Section}.Feature'),
                'required' => false,
            ])
            ->add('group_access', EntitySearchInputType::class, [
                // PS-specific type for M:M associations
                'label' => $this->trans('Group access', [], 'Admin.{Section}.Feature'),
                'required' => false,
                'multiple' => true,
            ]);
    }
}
```

Key PS-specific form types to know:
- `SwitchType` — yes/no toggle (renders as a Bootstrap switch)
- `TranslatableType` — wraps any type for i18n; produces `['en' => 'value', 'fr' => 'valeur']`
- `EntitySearchInputType` — async search-and-select for M:M relations (groups, zones, etc.)
- `ShopChoiceTreeType` — shop association tree (multistore)

### `SizeWeightSettingsType.php`

Simple type with `NumberType` fields for max width, height, depth, weight.

### Complex sub-types (ranges, price tables)

For complex dynamic data (rows × columns tables), create a `Type/` subdirectory:

```
Form/Admin/{Section}/{Subsection}/{Domain}/
├── {Domain}Type.php
├── GeneralSettingsType.php
├── ShippingLocationsAndCostsType.php
├── SizeWeightSettingsType.php
└── Type/
    ├── {Domain}RangesType.php   # PHP form type wrapping the Vue component
    ├── CostsRangeType.php       # Individual range row
    └── CostsZoneType.php        # Zone-specific cost cell
```

The PHP form type for a Vue-backed field typically uses `HiddenType` with a `data-` attribute that passes the initial JSON to the Vue component. The Vue component writes back to the hidden field on change.

## 7.4 — DataProvider

`src/Core/Form/IdentifiableObject/DataProvider/{Domain}FormDataProvider.php`

```php
final class {Domain}FormDataProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {}

    public function getData(int $id): array
    {
        $editable = $this->queryBus->handle(
            new Get{Domain}ForEditing(new {Domain}Id($id))
        );

        return [
            'general' => [
                'name' => $editable->getName(),
                'active' => $editable->isActive(),
                'delay' => $editable->getLocalizedDelay(),
                'grade' => $editable->getGrade(),
                'tracking_url' => $editable->getTrackingUrl(),
                'group_access' => $editable->getGroupIds(),
            ],
            'shipping' => [
                'shipping_method' => $editable->getShippingMethod(),
                'zones' => $editable->getZoneIds(),
                'ranges' => $editable->getRanges(), // JSON-serialisable for Vue
            ],
            'sizes' => [
                'max_width' => $editable->getMaxWidth(),
                'max_height' => $editable->getMaxHeight(),
                'max_depth' => $editable->getMaxDepth(),
                'max_weight' => $editable->getMaxWeight(),
            ],
        ];
    }

    public function getDefaultData(): array
    {
        return [
            'general' => [
                'active' => true,
                'grade' => 0,
                'group_access' => [],
            ],
            'shipping' => [
                'shipping_method' => ShippingMethod::BY_WEIGHT,
                'zones' => [],
                'ranges' => [],
            ],
            'sizes' => [
                'max_width' => 0,
                'max_height' => 0,
                'max_depth' => 0,
                'max_weight' => 0,
            ],
        ];
    }
}
```

The array structure **must** match the form type structure exactly (nested arrays matching nested form types).

## 7.5 — DataHandler

`src/Core/Form/IdentifiableObject/DataHandler/{Domain}FormDataHandler.php`

```php
final class {Domain}FormDataHandler implements FormDataHandlerInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus
    ) {}

    public function create(array $data): {Domain}Id
    {
        $command = new Add{Domain}Command(
            name: $data['general']['name'],
            active: (bool) $data['general']['active'],
        );

        // Optional fields
        if (!empty($data['general']['tracking_url'])) {
            $command->setTrackingUrl($data['general']['tracking_url']);
        }
        if (!empty($data['general']['group_access'])) {
            $command->setGroupIds(array_map('intval', $data['general']['group_access']));
        }

        // Map shipping tab
        $command->setShippingMethod($data['shipping']['shipping_method']);
        $command->setZoneIds(array_map('intval', $data['shipping']['zones'] ?? []));

        // Map sizes tab
        $command->setMaxWidth((float) ($data['sizes']['max_width'] ?? 0));

        $domainId = $this->commandBus->handle($command);

        // Sub-resource commands dispatched after the entity exists
        if (!empty($data['shipping']['ranges'])) {
            $this->commandBus->handle(new SetCarrierRangesCommand(
                $domainId,
                $this->buildRanges($data['shipping']['ranges'])
            ));
        }

        return $domainId;
    }

    public function update(int $id, array $data): void
    {
        $domainId = new {Domain}Id($id);

        $command = new Edit{Domain}Command($domainId);
        $command->setName($data['general']['name']);
        $command->setActive((bool) $data['general']['active']);
        // ... all fields

        $this->commandBus->handle($command);

        // Sub-resource updates dispatched separately
        $this->commandBus->handle(new Set{Domain}RangesCommand(
            $domainId,
            $this->buildRanges($data['shipping']['ranges'] ?? [])
        ));

        // Multistore-aware sub-resource (e.g. tax rule group)
        if (isset($data['shipping']['tax_rules_group_id'])) {
            $this->commandBus->handle(new Set{Domain}TaxRuleGroupCommand(
                $domainId,
                (int) $data['shipping']['tax_rules_group_id']
            ));
        }
    }
}
```

Multiple commands can be dispatched from a single `update()` — this is normal and expected. Compose at the handler level, not inside individual command handlers.

## 7.6 — Service registration

```yaml
# services/bundle/form/form_type.yml
PrestaShopBundle\Form\Admin\Improve\Shipping\{Domain}\{Domain}Type:
    tags: [form.type]

# services/bundle/form/form_data_provider.yml
prestashop.core.form.identifiable_object.data_provider.{domain}_form_data_provider:
    class: PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataProvider\{Domain}FormDataProvider
    arguments:
        - '@prestashop.core.query_bus'

# services/bundle/form/form_handler.yml
prestashop.core.form.identifiable_object.handler.{domain}_form_handler:
    class: PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Handler\FormHandler
    arguments:
        - '@form.factory'
        - '@prestashop.core.form.identifiable_object.data_handler.{domain}_form_data_handler'
        - '@prestashop.core.form.identifiable_object.data_provider.{domain}_form_data_provider'
        - 'PrestaShopBundle\Form\Admin\Improve\Shipping\{Domain}\{Domain}Type'

prestashop.core.form.identifiable_object.builder.{domain}_form_builder:
    class: PrestaShop\PrestaShop\Core\Form\IdentifiableObject\Builder\FormBuilder
    arguments:
        - '@form.factory'
        - '@prestashop.core.form.identifiable_object.data_provider.{domain}_form_data_provider'
        - 'PrestaShopBundle\Form\Admin\Improve\Shipping\{Domain}\{Domain}Type'
```

## Checklist

- [ ] `{Domain}Type.php` uses `NavigationTabType` as parent, one `add()` per tab
- [ ] One sub-form per tab created in the same directory
- [ ] `Type/` subdirectory created for complex field types (ranges, dynamic tables)
- [ ] PS-specific types used: `SwitchType`, `TranslatableType`, `EntitySearchInputType` where appropriate
- [ ] `{Domain}FormDataProvider::getData()` maps every DTO field to nested array matching form structure
- [ ] `{Domain}FormDataProvider::getDefaultData()` returns sensible defaults
- [ ] `{Domain}FormDataHandler::create()` builds and dispatches `Add{Domain}Command` + all sub-resource commands
- [ ] `{Domain}FormDataHandler::update()` builds and dispatches `Edit{Domain}Command` + all sub-resource commands
- [ ] All services registered in DI YAML (form_type, form_data_provider, form_data_handler, form_handler, form_builder)
