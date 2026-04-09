---
step: 4
title: "Grid"
skill: legacy-to-symfony-migration
previous: step-03-behat-tests.md
next: step-05-symfony-controller.md
deliverable: "src/Core/Grid/ components for the listing page: DefinitionFactory, QueryBuilder, DataFactory, SearchFilters"
---

# Step 4 — Grid

The PrestaShop grid system (`src/Core/Grid/`) powers the admin listing pages. It is composed of four loosely-coupled classes that the Symfony controller assembles via a `GridFactory`. The grid is independent of the form — it can be built and shipped before the add/edit form exists.

## Micro-Skills

> Full definitions: `.claude/migration-micro-skill-architecture.md`

| ID | Brick | Artifact | ⚠ |
|---|---|---|---|
| **G1** | `create-grid-definition-factory` | `Grid/Definition/Factory/{Domain}GridDefinitionFactory.php` | — |
| **G2** | `create-grid-query-builder` | `Grid/Query/{Domain}QueryBuilder.php` | — |
| **G3** | `create-grid-data-factory` | `Grid/Data/Factory/{Domain}GridDataFactory.php` | if computed cols |
| **G4** | `create-grid-search-filters` | `Core/Search/Filters/{Domain}Filters.php` | — |
| **G5** | `register-grid-services` | `services/core/grid/{domain}.yml` | — |

## 4.1 — GridDefinitionFactory

`src/Core/Grid/Definition/Factory/{Domain}GridDefinitionFactory.php`

This class declares the **structure** of the grid: which columns exist, what actions are available, and what filters are supported. It extends `AbstractGridDefinitionFactory`.

### Columns

```php
protected function getColumns(): ColumnCollection
{
    return (new ColumnCollection())
        ->add(
            (new DataColumn('id_{domain}'))
                ->setName($this->trans('ID', [], 'Admin.Global'))
                ->setOptions(['field' => 'id_{domain}'])
        )
        ->add(
            (new DataColumn('name'))
                ->setName($this->trans('Name', [], 'Admin.Global'))
                ->setOptions(['field' => 'name', 'sortable' => true])
        )
        ->add(
            // Image column (logo, thumbnail)
            (new ImageColumn('logo'))
                ->setName($this->trans('Logo', [], 'Admin.Global'))
                ->setOptions(['src_field' => 'image'])
        )
        ->add(
            // Toggle column for boolean fields
            (new ToggleColumn('active'))
                ->setName($this->trans('Active', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'active',
                    'primary_field' => 'id_{domain}',
                    'route' => 'admin_{domain}s_toggle_status',
                    'route_param_name' => '{domain}Id',
                ])
        )
        ->add(
            // Position column for drag-and-drop reordering
            (new PositionColumn('position'))
                ->setName($this->trans('Position', [], 'Admin.Global'))
                ->setOptions([
                    'field' => 'position',
                    'id_field' => 'id_{domain}',
                    'update_route' => 'admin_{domain}s_update_position',
                ])
        )
        ->add(
            (new ActionColumn('actions'))
                ->setName($this->trans('Actions', [], 'Admin.Global'))
                ->setOptions([
                    'actions' => $this->getRowActions(),
                ])
        );
}
```

### Row Actions

```php
private function getRowActions(): RowActionCollection
{
    return (new RowActionCollection())
        ->add(
            (new LinkRowAction('edit'))
                ->setIcon('edit')
                ->setOptions([
                    'route' => 'admin_{domain}s_edit',
                    'route_param_name' => '{domain}Id',
                    'route_param_field' => 'id_{domain}',
                    'clickable_row' => true,
                ])
        )
        ->add(
            (new DeleteRowAction('delete'))
                ->setOptions([
                    'route' => 'admin_{domain}s_delete',
                    'route_param_name' => '{domain}Id',
                    'route_param_field' => 'id_{domain}',
                    'confirm_message' => $this->trans('Delete selected item?', [], 'Admin.Notifications.Warning'),
                ])
        );
}
```

### Bulk Actions

```php
protected function getBulkActions(): BulkActionCollection
{
    return (new BulkActionCollection())
        ->add(
            (new SubmitBulkAction('enable_selection'))
                ->setName($this->trans('Enable selection', [], 'Admin.Actions'))
                ->setOptions(['submit_route' => 'admin_{domain}s_bulk_enable_status'])
        )
        ->add(
            (new SubmitBulkAction('disable_selection'))
                ->setName($this->trans('Disable selection', [], 'Admin.Actions'))
                ->setOptions(['submit_route' => 'admin_{domain}s_bulk_disable_status'])
        )
        ->add(
            (new DeleteBulkAction('delete_selection'))
                ->setName($this->trans('Delete selected', [], 'Admin.Actions'))
                ->setOptions([
                    'submit_route' => 'admin_{domain}s_bulk_delete',
                    'confirm_message' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
                ])
        );
}
```

### Filters

```php
protected function getFilters(): FilterCollection
{
    return (new FilterCollection())
        ->add(
            (new Filter('id_{domain}', NumberType::class))
                ->setAssociatedColumn('id_{domain}')
        )
        ->add(
            (new Filter('name', TextType::class))
                ->setAssociatedColumn('name')
                ->setTypeOptions(['required' => false, 'attr' => ['placeholder' => $this->trans('Search name', [], 'Admin.Actions')]])
        )
        ->add(
            (new Filter('active', YesAndNoChoiceType::class))
                ->setAssociatedColumn('active')
        );
}
```

## 4.2 — QueryBuilder

`src/Core/Grid/Query/{Domain}QueryBuilder.php`

Implements `DoctrineQueryBuilderInterface`. Uses DBAL (not Doctrine ORM). Constructs the SQL for the grid data.

```php
final class {Domain}QueryBuilder extends AbstractDoctrineQueryBuilder
{
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getBaseQuery();
        $qb->select('c.id_{domain}, c.name, c.active, c.position');

        // Apply filters
        $this->applyFilters($qb, $searchCriteria->getFilters());
        // Apply sorting
        if ($searchCriteria->getOrderBy()) {
            $qb->orderBy(
                'c.' . $searchCriteria->getOrderBy(),
                $searchCriteria->getOrderWay()
            );
        }
        // Apply pagination
        $qb->setFirstResult($searchCriteria->getOffset())
           ->setMaxResults($searchCriteria->getLimit());

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getBaseQuery();
        $qb->select('COUNT(DISTINCT c.id_{domain})');
        $this->applyFilters($qb, $searchCriteria->getFilters());
        return $qb;
    }

    private function getBaseQuery(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->from($this->dbPrefix . '{domain}', 'c')
            ->where('c.deleted = 0');
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['name']) && $filters['name'] !== '') {
            $qb->andWhere('c.name LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $qb->andWhere('c.active = :active')
               ->setParameter('active', $filters['active']);
        }
    }
}
```

Never use `Db::getInstance()` in QueryBuilder — DBAL only.

## 4.3 — DataFactory (if needed)

`src/Core/Grid/Data/Factory/{Domain}GridDataFactory.php`

Only needed when the raw SQL result requires post-processing — for example, converting a file path to a thumbnail URL, or resolving a FK to a display name.

```php
final class {Domain}GridDataFactory implements GridDataFactoryInterface
{
    public function __construct(
        private readonly GridDataFactoryInterface $decorated,
        private readonly string $imageBaseUrl,
    ) {}

    public function getData(SearchCriteriaInterface $searchCriteria): GridData
    {
        $data = $this->decorated->getData($searchCriteria);
        $records = $data->getRecords()->all();

        foreach ($records as &$record) {
            $record['image'] = $this->resolveLogoUrl($record['id_{domain}']);
        }

        return new GridData(
            new RecordCollection($records),
            $data->getResultsTotal(),
            $data->getTotalCount()
        );
    }
}
```

The DataFactory is registered as a **decorator** over the base grid data factory in services YAML.

## 4.4 — SearchFilters

`src/Core/Search/Filters/{Domain}Filters.php`

A thin class that declares which filters exist and their default values:

```php
final class {Domain}Filters extends Filters
{
    protected $filterId = '{domain}';

    public static function getDefaults(): array
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id_{domain}',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}
```

## 4.5 — PositionDefinition (if reorderable)

If the entity has a `position` field and drag-and-drop reordering:

```php
// In services YAML
prestashop.core.grid.{domain}.position_definition:
    class: PrestaShop\PrestaShop\Core\Grid\Position\PositionDefinition
    arguments:
        - '{domain}'           # table name (without prefix)
        - 'id_{domain}'        # primary key column
        - 'position'           # position column
```

## 4.6 — Service registration

```yaml
# src/PrestaShopBundle/Resources/config/services/core/grid/carrier.yml

prestashop.core.grid.definition.factory.{domain}:
    class: PrestaShop\PrestaShop\Core\Grid\Definition\Factory\{Domain}GridDefinitionFactory
    parent: prestashop.core.grid.definition.factory.abstract
    public: true

prestashop.core.grid.query_builder.{domain}:
    class: PrestaShop\PrestaShop\Core\Grid\Query\{Domain}QueryBuilder
    parent: prestashop.core.grid.abstract_query_builder

prestashop.core.grid.data.factory.{domain}_decorator:
    class: PrestaShop\PrestaShop\Core\Grid\Data\Factory\{Domain}GridDataFactory
    decorates: prestashop.core.grid.data.factory.{domain}
    arguments:
        - '@prestashop.core.grid.data.factory.{domain}_decorator.inner'
        - '%kernel.project_dir%/img/{domain}/'

prestashop.core.grid.factory.{domain}:
    class: PrestaShop\PrestaShop\Core\Grid\GridFactory
    arguments:
        - '@prestashop.core.grid.definition.factory.{domain}'
        - '@prestashop.core.grid.data.factory.{domain}_decorator'
        - '@prestashop.core.grid.query_builder.{domain}'
        - '@prestashop.core.hook.dispatcher'
```

Register the services file in `services.yml`.

## Checklist

- [ ] `{Domain}GridDefinitionFactory.php` with all columns (ID, name, image if any, toggle, position, actions)
- [ ] Bulk actions defined: enable, disable, delete
- [ ] Row actions defined: edit, delete
- [ ] Filters defined matching every filterable column
- [ ] `{Domain}QueryBuilder.php` with `getSearchQueryBuilder()` and `getCountQueryBuilder()` using DBAL
- [ ] `deleted = 0` filter in base query (if entity uses soft delete)
- [ ] Filters applied with `LIKE` for text, `=` for booleans/integers
- [ ] `{Domain}GridDataFactory.php` created only if image/computed columns need post-processing
- [ ] `{Domain}Filters.php` with defaults
- [ ] Position definition registered in services if reorderable
- [ ] All services registered in YAML and included in `services.yml`
