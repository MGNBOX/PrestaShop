<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Schema;

use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertySqlIndex;

/**
 * Manages the DDL (Data Definition Language) operations on extra storage tables.
 *
 * Responsible for creating, altering, and dropping the *_extra / *_extra_lang / *_extra_shop
 * tables and their custom columns. May be decorated with a cache-invalidation layer.
 */
interface ExtraPropertySchemaManagerInterface
{
    /**
     * Ensures that the extra table and its custom column exist.
     * Creates the table (copying the PK from the base entity table) if needed.
     * Creates the column (using the given SQL definition) if needed.
     * Synchronises the SQL index strategy on the column.
     *
     * @param string $entityName Normalized entity name (e.g. "product")
     * @param string $fieldScope Normalized scope: "common", "lang", or "shop"
     * @param string $columnName
     * @param string $sqlColumnDefinition Full SQL column definition fragment (from ColumnDefinitionMapper)
     * @param ExtraPropertySqlIndex $sqlIndex Index strategy to apply on the column
     */
    public function ensureExtraTableAndColumn(string $entityName, string $fieldScope, string $columnName, string $sqlColumnDefinition, ExtraPropertySqlIndex $sqlIndex): void;

    /**
     * Drops the custom column from the extra table when table and column exist.
     * Also drops the extra table itself when it becomes empty after the column removal.
     *
     * @param string $entityName Normalized entity name (e.g. "product")
     * @param string $fieldScope Normalized scope: "common", "lang", or "shop"
     * @param string $columnName
     */
    public function dropExtraColumnIfExists(string $entityName, string $fieldScope, string $columnName): void;
}
