<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Schema;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyDefinition;
use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertySqlIndex;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Manages the DDL of *_extra / *_extra_lang / *_extra_shop tables.
 *
 * Table naming convention (without DB prefix):
 *   entity scope → {entity}_extra          (e.g. product_extra)
 *   lang scope   → {entity}_extra_lang     (e.g. product_extra_lang)
 *   shop scope   → {entity}_extra_shop     (e.g. product_extra_shop)
 *
 * This service is BO-only: it is used exclusively during module install/uninstall flows,
 * which are back-office operations. The Symfony logger is therefore always available.
 */
class ExtraPropertySchemaManager implements ExtraPropertySchemaManagerInterface
{
    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $prefix,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function ensureExtraTableAndColumn(ExtraPropertyDefinition $definition): void
    {
        $baseTableName = $this->prefix . $definition->getBaseTableName();
        $extraTableName = $this->prefix . $definition->getExtraTableName();
        $columnName = $definition->getStorageColumnName();
        $sqlColumnDefinition = ColumnDefinitionMapper::getSqlDefinition($definition);
        $sqlIndex = $definition->getSqlIndex();

        if (!$this->tableExists($baseTableName)) {
            throw new RuntimeException(sprintf('The base table "%s" does not exist.', $baseTableName));
        }

        if (!$this->tableExists($extraTableName)) {
            $this->createExtraTableFromBaseTable($baseTableName, $extraTableName);
            $this->logger->info('Extra table created: {table}', ['table' => $extraTableName]);
        }

        if (!$this->columnExists($extraTableName, $columnName)) {
            if (!$this->isValidSqlIdentifier($columnName)) {
                throw new RuntimeException(sprintf('Invalid extra column name "%s".', $columnName));
            }

            $this->createExtraColumn($extraTableName, $columnName, $sqlColumnDefinition);
            $this->logger->info('Extra column created: {table}.{column}', ['table' => $extraTableName, 'column' => $columnName]);
        }

        $this->syncExtraColumnIndex($extraTableName, $columnName, $sqlIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function dropExtraColumnIfExists(ExtraPropertyDefinition $definition): void
    {
        $extraTableName = $this->prefix . $definition->getExtraTableName();
        $columnName = $definition->getStorageColumnName();

        if (!$this->tableExists($extraTableName) || !$this->columnExists($extraTableName, $columnName)) {
            return;
        }

        if (!$this->isValidSqlIdentifier($columnName)) {
            throw new RuntimeException(sprintf('Invalid extra column name "%s".', $columnName));
        }

        $sql = sprintf(
            'ALTER TABLE %s DROP COLUMN %s',
            $this->connection->quoteIdentifier($extraTableName),
            $this->connection->quoteIdentifier($columnName)
        );
        $this->connection->executeStatement($sql);
        $this->logger->info('Extra column dropped: {table}.{column}', ['table' => $extraTableName, 'column' => $columnName]);

        $this->dropExtraTableIfEmpty($extraTableName);
    }

    /**
     * Adds a new column to the extra table.
     *
     * @param string $extraTableName Full table name (with prefix)
     * @param string $columnName Column to add
     * @param string $sqlColumnDefinition SQL column definition fragment (from ColumnDefinitionMapper)
     */
    protected function createExtraColumn(string $extraTableName, string $columnName, string $sqlColumnDefinition): void
    {
        $sql = sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s',
            $this->connection->quoteIdentifier($extraTableName),
            $this->connection->quoteIdentifier($columnName),
            $sqlColumnDefinition
        );
        $this->connection->executeStatement($sql);
    }

    /**
     * Synchronises the SQL index on an extra column: drops stale indexes and creates the desired one.
     *
     * @param string $extraTableName Full table name (with prefix)
     * @param string $columnName
     * @param ExtraPropertySqlIndex $sqlIndex Desired index strategy
     */
    protected function syncExtraColumnIndex(string $extraTableName, string $columnName, ExtraPropertySqlIndex $sqlIndex): void
    {
        $keyIndexName = $this->buildExtraColumnIndexName($extraTableName, $columnName, ExtraPropertySqlIndex::KEY);
        $uniqueIndexName = $this->buildExtraColumnIndexName($extraTableName, $columnName, ExtraPropertySqlIndex::UNIQUE);

        // Drop any index that no longer matches the desired strategy.
        if (ExtraPropertySqlIndex::KEY !== $sqlIndex) {
            $this->dropIndexIfExists($extraTableName, $keyIndexName);
        }
        if (ExtraPropertySqlIndex::UNIQUE !== $sqlIndex) {
            $this->dropIndexIfExists($extraTableName, $uniqueIndexName);
        }

        if (ExtraPropertySqlIndex::NONE === $sqlIndex) {
            return;
        }

        // Create the desired index if it does not already exist.
        $indexName = (ExtraPropertySqlIndex::UNIQUE === $sqlIndex) ? $uniqueIndexName : $keyIndexName;
        if ($this->indexExists($extraTableName, $indexName)) {
            return;
        }

        $sql = sprintf(
            'ALTER TABLE %s ADD %s %s (%s)',
            $this->connection->quoteIdentifier($extraTableName),
            ExtraPropertySqlIndex::UNIQUE === $sqlIndex ? 'UNIQUE INDEX' : 'INDEX',
            $this->connection->quoteIdentifier($indexName),
            $this->connection->quoteIdentifier($columnName)
        );
        $this->connection->executeStatement($sql);
    }

    /**
     * Drops the extra table if all its columns are part of the primary key (i.e. no extra columns remain).
     *
     * @param string $extraTableName Full table name (with prefix)
     */
    protected function dropExtraTableIfEmpty(string $extraTableName): void
    {
        $tableDetails = $this->connection->createSchemaManager()->introspectTable($extraTableName);
        $primaryKey = $tableDetails->getPrimaryKey();
        if (null === $primaryKey) {
            return;
        }

        $primaryKeyColumns = array_flip($primaryKey->getColumns());
        foreach (array_keys($tableDetails->getColumns()) as $columnName) {
            if (!array_key_exists($columnName, $primaryKeyColumns)) {
                return;
            }
        }

        $sql = sprintf('DROP TABLE %s', $this->connection->quoteIdentifier($extraTableName));
        $this->connection->executeStatement($sql);
        $this->logger->info('Extra table dropped (empty): {table}', ['table' => $extraTableName]);
    }

    /**
     * Creates the extra table by mirroring the primary key columns of the base entity table.
     *
     * @param string $baseTableName Full base table name (with prefix)
     * @param string $extraTableName Full extra table name (with prefix)
     *
     * @throws RuntimeException if the base table schema cannot be loaded or has no PK
     */
    protected function createExtraTableFromBaseTable(string $baseTableName, string $extraTableName): void
    {
        $baseTableDetails = $this->connection->createSchemaManager()->introspectTable($baseTableName);
        $primaryKey = $baseTableDetails->getPrimaryKey();
        if (null === $primaryKey) {
            throw new RuntimeException(sprintf('The base table "%s" has no primary key.', $baseTableName));
        }

        $platform = $this->connection->getDatabasePlatform();
        $columnDefinitions = [];

        foreach ($primaryKey->getColumns() as $primaryColumnName) {
            $primaryColumn = $baseTableDetails->getColumn($primaryColumnName);
            $columnOptions = $this->buildColumnDeclarationOptions($primaryColumn);
            $columnDefinitions[] = $platform->getColumnDeclarationSQL($primaryColumnName, $columnOptions);
        }

        $primaryKeyColumns = array_map(
            fn (string $col): string => $this->connection->quoteIdentifier($col),
            $primaryKey->getColumns()
        );

        $sql = sprintf(
            'CREATE TABLE %s (%s, PRIMARY KEY (%s))',
            $this->connection->quoteIdentifier($extraTableName),
            implode(', ', $columnDefinitions),
            implode(', ', $primaryKeyColumns)
        );
        $this->connection->executeStatement($sql);
    }

    /**
     * Builds the options array needed by Doctrine's getColumnDeclarationSQL() for a given column.
     *
     * @param \Doctrine\DBAL\Schema\Column $column
     *
     * @return array<string, mixed>
     */
    protected function buildColumnDeclarationOptions(\Doctrine\DBAL\Schema\Column $column): array
    {
        $columnOptions = [
            'type' => $column->getType(),
            'precision' => $column->getPrecision(),
            'scale' => $column->getScale(),
            'unsigned' => $column->getUnsigned(),
            'fixed' => $column->getFixed(),
            'notnull' => true,
            'default' => $column->getDefault(),
            'autoincrement' => false,
        ];

        if (null !== $column->getLength()) {
            $columnOptions['length'] = $column->getLength();
        }
        if ('' !== $column->getComment()) {
            $columnOptions['comment'] = $column->getComment();
        }
        foreach (['charset', 'collation'] as $platformOptionName) {
            if ($column->hasPlatformOption($platformOptionName)) {
                $columnOptions[$platformOptionName] = $column->getPlatformOption($platformOptionName);
            }
        }

        return $columnOptions;
    }

    /**
     * @param string $tableName Full table name (with prefix)
     *
     * @return bool
     */
    protected function tableExists(string $tableName): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * @param string $tableName Full table name (with prefix)
     * @param string $columnName
     *
     * @return bool
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        if (!$this->tableExists($tableName)) {
            return false;
        }

        return $this->connection->createSchemaManager()->introspectTable($tableName)->hasColumn($columnName);
    }

    /**
     * @param string $tableName Full table name (with prefix)
     * @param string $indexName
     *
     * @return bool
     */
    protected function indexExists(string $tableName, string $indexName): bool
    {
        if (!$this->tableExists($tableName)) {
            return false;
        }

        return $this->connection->createSchemaManager()->introspectTable($tableName)->hasIndex($indexName);
    }

    /**
     * @param string $tableName Full table name (with prefix)
     * @param string $indexName
     */
    protected function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        $sql = sprintf(
            'ALTER TABLE %s DROP INDEX %s',
            $this->connection->quoteIdentifier($tableName),
            $this->connection->quoteIdentifier($indexName)
        );
        $this->connection->executeStatement($sql);
    }

    /**
     * Builds a deterministic index name for an extra column based on table name, column name and index type.
     *
     * @param string $tableName Full table name (with prefix)
     * @param string $columnName
     * @param ExtraPropertySqlIndex $sqlIndex
     *
     * @return string
     */
    protected function buildExtraColumnIndexName(string $tableName, string $columnName, ExtraPropertySqlIndex $sqlIndex): string
    {
        $prefix = (ExtraPropertySqlIndex::UNIQUE === $sqlIndex) ? 'uniq_extra_' : 'idx_extra_';

        return $prefix . substr(sha1($tableName . '|' . $columnName), 0, 16);
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    protected function isValidSqlIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier);
    }
}
