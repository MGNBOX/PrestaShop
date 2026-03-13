<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Grid;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DateTimeColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition;
use PrestaShop\PrestaShop\Core\Grid\Exception\ColumnNotFoundException;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds extra properties columns and filters into BO Symfony grids.
 */
class ExtraPropertiesGridDefinitionModifier
{
    public function __construct(
        protected readonly ExtraPropertiesGridDefinitionProvider $definitionProvider,
        protected readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param GridDefinition $definition
     * @param string $gridId Grid identifier (usually equals entity table name, e.g. "product")
     */
    public function apply(GridDefinition $definition, string $gridId): void
    {
        $definitions = $this->definitionProvider->getDefinitionsForGrid($gridId);
        if (empty($definitions)) {
            return;
        }

        $columns = $definition->getColumns();
        $filters = $definition->getFilters();

        foreach ($definitions as $extraDefinition) {
            $fieldName = (string) ($extraDefinition['field_name'] ?? '');
            if ('' === $fieldName) {
                continue;
            }

            $moduleName = ExtraPropertyNaming::displayModuleKey($extraDefinition['module_name'] ?? null);
            $scope = (string) ($extraDefinition['field_scope'] ?? 'common');

            $columnId = ExtraPropertyNaming::formFieldName($moduleName, $fieldName, $scope);
            if ($this->hasColumnId($columns, $columnId)) {
                continue;
            }

            $label = $this->translateLabel(
                $extraDefinition,
                'title_wording',
                'title_domain',
                $this->translator->trans(ucfirst(str_replace('_', ' ', $fieldName)), [], 'Admin.Global')
            );

            $column = $this->buildColumn($gridId, $columnId, $label, $extraDefinition);

            $positionRef = trim((string) ($extraDefinition['grid_position'] ?? ''));
            if ('' !== $positionRef) {
                try {
                    $columns->addAfter($positionRef, $column);
                } catch (ColumnNotFoundException) {
                    $this->addBeforeActionsOrAtEnd($columns, $column);
                }
            } else {
                $this->addBeforeActionsOrAtEnd($columns, $column);
            }

            [$filterType, $filterOptions] = $this->resolveFilterTypeAndOptions($extraDefinition);
            $filters->add(
                (new Filter($columnId, $filterType))
                    ->setAssociatedColumn($columnId)
                    ->setTypeOptions($filterOptions)
            );
        }
    }

    protected function buildColumn(string $gridId, string $columnId, string $label, array $definition): ColumnInterface
    {
        $declaredType = !empty($definition['symfony_field_type']) ? (string) $definition['symfony_field_type'] : null;
        $scope = (string) ($definition['field_scope'] ?? 'common');
        $moduleName = ExtraPropertyNaming::displayModuleKey($definition['module_name'] ?? null);
        $fieldName = (string) ($definition['field_name'] ?? '');

        if (CheckboxType::class === $declaredType && '' !== $fieldName) {
            $primaryField = 'id_' . $gridId;
            $legacyController = $this->guessLegacyController($gridId);
            $entityName = (string) ($definition['entity_name'] ?? $gridId);

            return (new ToggleColumn($columnId))
                ->setName($label)
                ->setOptions([
                    'field' => $columnId,
                    'primary_field' => $primaryField,
                    'route' => 'admin_common_extra_properties_toggle',
                    'route_param_name' => 'entityId',
                    'extra_route_params' => [
                        'entityName' => $entityName,
                        'moduleName' => $moduleName,
                        'fieldName' => $fieldName,
                        'scope' => $scope,
                        'shopId' => 'id_shop_default',
                        '_legacy_controller' => $legacyController,
                    ],
                ]);
        }

        if (DateTimeType::class === $declaredType) {
            return (new DateTimeColumn($columnId))
                ->setName($label)
                ->setOptions([
                    'field' => $columnId,
                    'sortable' => true,
                    'clickable' => false,
                ]);
        }

        return (new DataColumn($columnId))
            ->setName($label)
            ->setOptions([
                'field' => $columnId,
                'sortable' => true,
                'clickable' => false,
            ]);
    }

    protected function guessLegacyController(string $entityName): string
    {
        return 'Admin' . ucfirst($entityName) . 's';
    }

    /**
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    protected function resolveFilterTypeAndOptions(array $definition): array
    {
        $declaredType = !empty($definition['symfony_field_type']) ? (string) $definition['symfony_field_type'] : null;
        if (CheckboxType::class === $declaredType) {
            return [YesAndNoChoiceType::class, ['required' => false]];
        }

        return [TextType::class, ['required' => false]];
    }

    protected function addBeforeActionsOrAtEnd(ColumnCollectionInterface $columns, ColumnInterface $column): void
    {
        if ($this->hasColumnId($columns, 'actions')) {
            $columns->addBefore('actions', $column);

            return;
        }

        $columns->add($column);
    }

    protected function hasColumnId(ColumnCollectionInterface $columns, string $id): bool
    {
        foreach ($columns as $column) {
            if ($column instanceof ColumnInterface && $id === $column->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $definition
     * @param string $wordingKey
     * @param string $domainKey
     * @param string $default
     *
     * Runtime translation path for BO grids:
     * - reads wording/domain pairs from extra_property_definition
     * - translates with Symfony translator in the current BO language
     * - falls back to $default when wording is missing
     * - falls back to Admin.Global when domain is missing
     */
    protected function translateLabel(array $definition, string $wordingKey, string $domainKey, string $default): string
    {
        $wording = isset($definition[$wordingKey]) && is_scalar($definition[$wordingKey])
            ? trim((string) $definition[$wordingKey])
            : '';
        if ('' === $wording) {
            return $default;
        }

        $domain = isset($definition[$domainKey]) && is_scalar($definition[$domainKey])
            ? trim((string) $definition[$domainKey])
            : 'Admin.Global';

        return $this->translator->trans($wording, [], $domain);
    }
}
