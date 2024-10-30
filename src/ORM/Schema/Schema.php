<?php

namespace Vest\ORM\Schema;

use Vest\ORM\QueryBuilder;
use Vest\ORM\Connection;

class Schema
{
    protected static Connection $connection;

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $driver = self::getDriverName();;

        $sql = $blueprint->toSql($driver);

        self::executeStatement($sql);

        // Criar índices
        foreach ($blueprint->getIndexes() as $index) {
            $indexSql = self::createIndexSql($table, $index, $driver);
            self::executeStatement($indexSql);
        }

        // Criar chaves estrangeiras
        foreach ($blueprint->getForeignKeys() as $foreign) {
            $foreignSql = self::createForeignKeySql($table, $foreign, $driver);
            self::executeStatement($foreignSql);
        }
    }

    public static function dropIfExists(string $table): void
    {
        $driver = self::getDriverName();
        $quotedTable = self::quoteIdentifier($table, $driver);
        $sql = "DROP TABLE IF EXISTS {$quotedTable}";
        self::executeStatement($sql);
    }

    protected static function createIndexSql(string $table, array $index, string $driver): string
    {
        $quotedTable = self::quoteIdentifier($table, $driver);
        $indexName = $index['name'] ?? "{$table}_{$index['columns'][0]}_index";
        $quotedIndexName = self::quoteIdentifier($indexName, $driver);
        $columns = implode(', ', array_map(function ($column) use ($driver) {
            return self::quoteIdentifier($column, $driver);
        }, $index['columns']));

        $type = '';
        if (isset($index['type'])) {
            if ($index['type'] === 'unique') {
                $type = 'UNIQUE ';
            }
        }

        return "CREATE {$type}INDEX {$quotedIndexName} ON {$quotedTable} ({$columns})";
    }

    protected static function createForeignKeySql(string $table, array $foreign, string $driver): string
    {
        $quotedTable = self::quoteIdentifier($table, $driver);
        $quotedColumn = self::quoteIdentifier($foreign['column'], $driver);
        $quotedForeignTable = self::quoteIdentifier($foreign['on'], $driver);
        $quotedForeignColumn = self::quoteIdentifier($foreign['references'], $driver);

        $constraintName = $foreign['name'] ?? "{$table}_{$foreign['column']}_foreign";
        $quotedConstraintName = self::quoteIdentifier($constraintName, $driver);

        $sql = "ALTER TABLE {$quotedTable} ADD CONSTRAINT {$quotedConstraintName} ";
        $sql .= "FOREIGN KEY ({$quotedColumn}) REFERENCES {$quotedForeignTable} ({$quotedForeignColumn})";

        if (isset($foreign['onDelete'])) {
            $sql .= " ON DELETE {$foreign['onDelete']}";
        }

        if (isset($foreign['onUpdate'])) {
            $sql .= " ON UPDATE {$foreign['onUpdate']}";
        }

        return $sql;
    }

    protected static function executeStatement(string $sql): void
    {
        try {
            $connection = self::$connection->getConnection();
            $connection->exec($sql);
        } catch (\PDOException $e) {
            echo "Erro ao executar SQL: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    protected static function getDriverName(): string
    {
        $driver = self::$connection->getDriver()::class;
        return $driver;
    }

    protected static function quoteIdentifier(string $identifier, string $driver): string
    {
        switch ($driver) {
            case 'Vest\ORM\MysqlDriver':
                return "`{$identifier}`";
            case 'Vest\ORM\PgsqlDriver':
            case 'Vest\ORM\SqliteDriver':
                return "\"{$identifier}\"";
            case 'Vest\ORM\SqlsrvDriver':
                return "[{$identifier}]";
            default:
                return $identifier;
        }
    }
}
