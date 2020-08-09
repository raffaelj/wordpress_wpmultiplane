<?php
declare(strict_types=1);

namespace MongoSql\QueryBuilder;

use ErrorException;
use InvalidArgumentException;

use MongoSql\QueryBuilder\QueryBuilder;

/**
 * MySQL query builder
 * @see {@link https://dev.mysql.com/doc/refman/5.7/en/json-function-reference.html}
 * Values quoted by ', escaped by \'
 */
class MysqlQueryBuilder extends QueryBuilder
{
    /**
     * @inheritdoc
     * @see {@link https://dev.mysql.com/doc/refman/5.7/en/json.html#json-paths}
     */
    public function createPathSelector(string $fieldName): string
    {
        $segments = static::splitPath($fieldName);
        $mysqlPath = array_reduce($segments, function (string $carry, string $item): string {
            $pattern = is_numeric($item)
                ? '%s[%d]'
                : '%s.%s';

            return sprintf($pattern, $carry, $item);
        }, '$');

        // MySQL 5.7.8 & MariaDB 10.2.3
        // {@link https://jira.mariadb.org/browse/MDEV-13594}
        return sprintf('JSON_UNQUOTE(JSON_EXTRACT(`document`, %s))', $this->qv($mysqlPath));

        // MySQL 5.7.9
        // return sprintf('`document` ->> %s', $this->qv($mysqlPath));
    }

    /**
     * @inheritdoc
     */
    protected function buildWhereSegment(string $func, string $fieldName, $value): ?string
    {
        $pathSelector = $this->createPathSelector($fieldName);

        switch ($func) {
            case '$eq':
                return vsprintf('%s = %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$ne':
                return vsprintf('%s <> %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$gte':
                return vsprintf('%s >= %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$gt':
                return vsprintf('%s > %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$lte':
                return vsprintf('%s <= %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$lt':
                return vsprintf('%s < %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            // When db value is an array, this evaluates to false
            // Could use JSON_OVERLAPS but it's MySQL 8+
            case '$in':
                return vsprintf('%s IN (%s)', [
                    $pathSelector,
                    $this->qvs($value),
                ]);

            case '$nin':
                return vsprintf('%s NOT IN (%s)', [
                    $pathSelector,
                    $this->qvs($value),
                ]);

            case '$has':
                if (!is_string($value)) {
                    throw new InvalidArgumentException('Invalid argument for $has array not supported');
                }

                return vsprintf('JSON_CONTAINS(%s, JSON_QUOTE(%s))', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$all':
                if (!is_array($value)) {
                    throw new InvalidArgumentException('Invalid argument for $all option must be array');
                }

                return vsprintf('JSON_CONTAINS(%s, JSON_ARRAY(%s))', [
                    $pathSelector,
                    $this->qvs($value),
                ]);

            // Note cockpit default is case sensitive
            // Note: ^ doesn't work
            case '$preg':
            case '$match':
            case '$regex':
                return vsprintf('LOWER(%s) REGEXP LOWER(%s)', [
                    $pathSelector,
                    // Escape \ and trim /
                    $this->qv(trim(str_replace('\\', '\\\\', $value), '/')),
                ]);

            case '$size':
                return vsprintf('JSON_LENGTH(%s) = %s', [
                    $pathSelector,
                    $this->qv($value),
                ]);

            case '$mod':
                if (!is_array($value)) {
                    throw new InvalidArgumentException('Invalid argument for $mod option must be array');
                }

                return vsprintf('MOD(%s, %s) = %d', [
                    $pathSelector,
                    // Remainder
                    $this->qv($value[0]),
                    // Divisor
                    $this->qv($value[1] ?? 0),
                ]);

            case '$func':
            case '$fn':
            case '$f':
                throw new InvalidArgumentException(sprintf('Function %s not supported by database driver', $func), 1);

            // Warning: doesn't check if key exists
            case '$exists':
                return vsprintf('%s %s NULL', [
                    $pathSelector,
                    $value ? 'IS NOT' : 'IS'
                ]);

            // Note: no idea how to implement. SOUNDEX doesn't search in strings.
            case '$fuzzy':
                throw new InvalidArgumentException(sprintf('Function %s not supported by database driver', $func), 1);

            case '$text':
                if (is_array($value)) {
                    throw new InvalidArgumentException(sprintf('Options for %s function are not suppored by database driver', $func), 1);
                }

                return vsprintf('%s LIKE %s', [
                    $pathSelector,
                    $this->qv(static::wrapLikeValue($value))
                ]);

            // Skip Mongo specific stuff
            case '$options':
                break;

            default:
                throw new ErrorException(sprintf('Condition not valid ... Use %s for custom operations', $func));
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function buildTableExists(string $tableName): string
    {
        return sprintf("SHOW TABLES LIKE '%s'", $tableName);
    }

    /**
     * @inheritdoc
     */
    public function buildCreateTable(string $tableName): string
    {
        return <<<SQL

            CREATE TABLE IF NOT EXISTS {$this->qi($tableName)} (
                "id"       INT  NOT NULL AUTO_INCREMENT,
                "document" JSON NOT NULL,
                -- Add generated column with unique key
                "_id_virtual"       VARCHAR(24) GENERATED ALWAYS AS ({$this->createPathSelector('_id')}) UNIQUE COMMENT 'Id',
                PRIMARY KEY ("id"),
                CONSTRAINT "_id_virtual_not_null" CHECK ("_id_virtual" IS NOT NULL)
            ) ENGINE=InnoDB COLLATE 'utf8mb4_unicode_ci';
SQL;
    }

    /**
     * @inheritdoc
     */
    public function qi(string $identifier): string
    {
        return sprintf('`%s`', str_replace('`', '``', $identifier));
    }
}
