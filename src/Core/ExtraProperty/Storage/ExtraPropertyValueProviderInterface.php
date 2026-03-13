<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Storage;

interface ExtraPropertyValueProviderInterface
{
    /**
     * Finds one extra field definition for an entity and field name.
     *
     * When $fieldScope is null:
     * - if a single definition exists, it is returned;
     * - if multiple scoped definitions exist, returns null (ambiguous).
     *
     * @param string $entityName
     * @param string $fieldName
     * @param string|null $fieldScope Allowed values: common, lang, shop or null
     *
     * @return array<string, mixed>|null
     */
    public function findCustomFieldDefinition(string $entityName, string $fieldName, ?string $fieldScope = null): ?array;

    /**
     * Returns extra properties grouped by module technical name.
     *
     * Returned format:
     * [
     *     'module_technical_name' => [
     *         'field_name' => 'value',
     *     ],
     * ]
     *
     * @param string $entityName
     * @param string $primaryKeyName
     * @param int $entityId
     * @param int|null $langId
     * @param int|null $shopId
     * @param bool $isLangMultishop
     * @param bool $displayFrontOnly
     *
     * @return array<string, array<string, mixed>>
     */
    public function getExtraProperties(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        ?int $langId = null,
        ?int $shopId = null,
        bool $isLangMultishop = false,
        bool $displayFrontOnly = false
    ): array;

    /**
     * Returns extra properties for front-office contexts only.
     *
     * This method centralizes FO conventions:
     * - only definitions with display_front = 1 are exposed;
     * - lang scope resolution is shop-aware in FO.
     *
     * @param string $entityName
     * @param string $primaryKeyName
     * @param int $entityId
     * @param int|null $langId
     * @param int|null $shopId
     *
     * @return array<string, array<string, mixed>>
     */
    public function getFrontExtraProperties(
        string $entityName,
        string $primaryKeyName,
        int $entityId,
        ?int $langId = null,
        ?int $shopId = null
    ): array;
}
