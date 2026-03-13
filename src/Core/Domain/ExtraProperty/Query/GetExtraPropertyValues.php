<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Query;

/**
 * Query to read extra property values for a single entity instance.
 *
 * Returns values for all three scopes (common, lang, shop), grouped by module name.
 * Lang-scope values are indexed by id_lang; the caller is responsible for any
 * locale-string conversion needed for presentation (e.g. Admin API responses).
 *
 * When $displayApiOnly is true, only definitions flagged display_api = 1 are read,
 * avoiding unnecessary DB columns in API responses.
 */
class GetExtraPropertyValues
{
    /**
     * @param string $entityName Entity table name (e.g. 'product')
     * @param string $primaryKeyName PK column name (e.g. 'id_product')
     * @param int $entityId Entity primary key value
     * @param bool $displayApiOnly When true, restrict to definitions with display_api = 1
     */
    public function __construct(
        protected readonly string $entityName,
        protected readonly string $primaryKeyName,
        protected readonly int $entityId,
        protected readonly bool $displayApiOnly = false,
    ) {
    }

    /**
     * Returns the entity table name (e.g. 'product').
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Returns the primary key column name (e.g. 'id_product').
     */
    public function getPrimaryKeyName(): string
    {
        return $this->primaryKeyName;
    }

    /**
     * Returns the entity primary key value.
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Returns true when only API-visible definitions should be included in the result.
     */
    public function isDisplayApiOnly(): bool
    {
        return $this->displayApiOnly;
    }
}
