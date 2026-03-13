<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use PrestaShop\PrestaShop\Core\ExtraProperty\ExtraPropertyNaming;
use PrestaShop\PrestaShop\Core\ExtraProperty\Schema\ExtraPropertySchemaManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Manages the DDL of *_extra / *_extra_lang / *_extra_shop tables.
 *
 * Table naming convention (without DB prefix):
 *   entity scope → {entity}_extra          (e.g. product_extra)
 *   lang scope   → {entity}_extra_lang     (e.g. product_extra_lang)
 *   shop scope   → {entity}_extra_shop     (e.g. product_extra_shop)
 */
class ExtraPropertySchemaManager implements ExtraPropertySchemaManagerInterface
{
    public const SQL_INDEX_NONE = 'none';
    public const SQL_INDEX_KEY = 'key';
    public const SQL_INDEX_UNIQUE = 'unique';

    protected const ALLOWED_SQL_INDEXES = [
        self::SQL_INDEX_NONE,
        self::SQL_INDEX_KEY,
        self::SQL_INDEX_UNIQUE,
    ];

    /** @var array<string, bool> */
    protected array $tableExistenceCache = [];

    /** @var array<string, Table> */
    protected array $tableDetailsCache = [];

    protected readonly LoggerInterface $logger;

    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $prefix,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function ensureExtraTableAndColumn(string $entityName, string $fieldScope, string $columnName, string $sqlColumnDefinition, string $sqlIndex): void
    {
        $baseTableName = $this->prefix . $this->buildBaseEntityTableName($entityName, $fieldScope);
        $extraTableName = $this->prefix . $this->buildExtraEntityTableName($entityName, $fieldScope);

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

            $sql = sprintf(
                'ALTER TABLE %s ADD COLUMN %s %s',
                $this->connection->quoteIdentifier($extraTableName),
                $this->connection->quoteIdentifier($columnName),
                $sqlColumnDefinition
            );
            $this->connection->executeStatement($sql);
            $this->invalidateTableCache($extraTableName);
            $this->logger->info('Extra column created: {table}.{column}', ['table' => $extraTableName, 'column' => $columnName]);
        }

        $this->syncExtraColumnIndex($extraTableName, $columnName, $sqlIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function dropExtraColumnIfExists(string $entityName, string $fieldScope, string $columnName): void
    {
        $extraTableName = $this->prefix . $this->buildExtraEntityTableName($entityName, $fieldScope);
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
        $this->invalidateTableCache($extraTableName);
        $this->logger->info('Extra column dropped: {table}.{column}', ['table' => $extraTableName, 'column' => $columnName]);

        $this->dropExtraTableIfEmpty($extraTableName);
    }

    /**
     * Returns the base (non-extra) entity table name for a given scope.
     */
    protected function buildBaseEntityTableName(string $entityName, string $fieldScope): string
    {
        if ('lang' === $fieldScope) {
            return $entityName . '_lang';
        }
        if ('shop' === $fieldScope) {
            return $entityName . '_shop';
        }

        return $entityName;
    }

    /**
     * Returns the extra storage table name (without prefix) for a given entity and scope.
     */
    protected function buildExtraEntityTableName(string $entityName, string $fieldScope): string
    {
        return ExtraPropertyNaming::extraTableName($entityName, $fieldScope);
    }

    protected function syncExtraColumnIndex(string $extraTableName, string $columnName, string $sqlIndex): void
    {
        $normalizedSqlIndex = $this->normalizeSqlIndex($sqlIndex);
        if (null === $normalizedSqlIndex) {
            throw new RuntimeException('Invalid extra field SQL index strategy.');
        }

        $keyIndexName = $this->buildExtraColumnIndexName($extraTableName, $columnName, self::SQL_INDEX_KEY);
        $uniqueIndexName = $this->buildExtraColumnIndexName($extraTableName, $columnName, self::SQL_INDEX_UNIQUE);

        if (self::SQL_INDEX_KEY !== $normalizedSqlIndex) {
            $this->dropIndexIfExists($extraTableName, $keyIndexName);
        }
        if (self::SQL_INDEX_UNIQUE !== $normalizedSqlIndex) {
            $this->dropIndexIfExists($extraTableName, $uniqueIndexName);
        }

        if (self::SQL_INDEX_NONE === $normalizedSqlIndex) {
            return;
        }

        $indexName = (self::SQL_INDEX_UNIQUE === $normalizedSqlIndex) ? $uniqueIndexName : $keyIndexName;
        if ($this->indexExists($extraTableName, $indexName)) {
            return;
        }

        $sql = sprintf(
            'ALTER TABLE %s ADD %s %s (%s)',
            $this->connection->quoteIdentifier($extraTableName),
            $normalizedSqlIndex === self::SQL_INDEX_UNIQUE ? 'UNIQUE INDEX' : 'INDEX',
            $this->connection->quoteIdentifier($indexName),
            $this->connection->quoteIdentifier($columnName)
        );
        $this->connection->executeStatement($sql);
        $this->invalidateTableCache($extraTableName);
    }

    protected function dropExtraTableIfEmpty(string $extraTableName): void
    {
        $tableDetails = $this->getTableDetails($extraTableName);
        if (null === $tableDetails) {
            return;
        }

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
        $this->invalidateTableCache($extraTableName);
        $this->logger->info('Extra table dropped (empty): {table}', ['table' => $extraTableName]);
    }

    protected function createExtraTableFromBaseTable(string $baseTableName, string $extraTableName): void
    {
        $baseTableDetails = $this->getTableDetails($baseTableName);
        if (null === $baseTableDetails) {
            throw new RuntimeException(sprintf('The schema for base table "%s" cannot be loaded.', $baseTableName));
        }

        $primaryKey = $baseTableDetails->getPrimaryKey();
        if (null === $primaryKey) {
            throw new RuntimeException(sprintf('The base table "%s" has no primary key.', $baseTableName));
        }

        $platform = $this->connection->getDatabasePlatform();
        $columnDefinitions = [];

        foreach ($primaryKey->getColumns() as $primaryColumnName) {
            if (!$baseTableDetails->hasColumn($primaryColumnName)) {
                throw new RuntimeException(sprintf(
                    'Primary key column "%s" was not found on base table "%s".',
                    $primaryColumnName,
                    $baseTableName
                ));
            }

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
        $this->invalidateTableCache($extraTableName);
    }

    /**
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

    protected function tableExists(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistenceCache)) {
            return $this->tableExistenceCache[$tableName];
        }
        $exists = $this->connection->createSchemaManager()->tablesExist([$tableName]);
        $this->tableExistenceCache[$tableName] = $exists;

        return $exists;
    }

    protected function columnExists(string $tableName, string $columnName): bool
    {
        $tableDetails = $this->getTableDetails($tableName);
        if (null === $tableDetails) {
            return false;
        }

        return $tableDetails->hasColumn($columnName);
    }

    protected function indexExists(string $tableName, string $indexName): bool
    {
        $tableDetails = $this->getTableDetails($tableName);
        if (null === $tableDetails) {
            return false;
        }

        return $tableDetails->hasIndex($indexName);
    }

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
        $this->invalidateTableCache($tableName);
    }

    protected function getTableDetails(string $tableName): ?Table
    {
        if (array_key_exists($tableName, $this->tableDetailsCache)) {
            return $this->tableDetailsCache[$tableName];
        }
        if (!$this->tableExists($tableName)) {
            return null;
        }

        $tableDetails = $this->connection->createSchemaManager()->introspectTable($tableName);
        $this->tableDetailsCache[$tableName] = $tableDetails;

        return $tableDetails;
    }

    protected function invalidateTableCache(string $tableName): void
    {
        unset($this->tableExistenceCache[$tableName], $this->tableDetailsCache[$tableName]);
    }

    protected function buildExtraColumnIndexName(string $tableName, string $columnName, string $sqlIndex): string
    {
        $prefix = (self::SQL_INDEX_UNIQUE === $sqlIndex) ? 'uniq_extra_' : 'idx_extra_';

        return $prefix . substr(sha1($tableName . '|' . $columnName), 0, 16);
    }

    protected function normalizeSqlIndex(string $sqlIndex): ?string
    {
        $normalizedSqlIndex = strtolower(trim($sqlIndex));
        if ('index' === $normalizedSqlIndex) {
            $normalizedSqlIndex = self::SQL_INDEX_KEY;
        }
        if (!in_array($normalizedSqlIndex, self::ALLOWED_SQL_INDEXES, true)) {
            return null;
        }

        return $normalizedSqlIndex;
    }

    protected function isValidSqlIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]{1,64}$/', $identifier);
    }
}
