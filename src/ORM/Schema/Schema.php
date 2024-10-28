<?php

namespace Vest\ORM\Schema;

use Vest\ORM\QueryBuilder;

class Schema
{
    protected static QueryBuilder $queryBuilder;

    public static function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        self::$queryBuilder = $queryBuilder;
    }

    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        // Gera o SQL para criar a tabela
        $sql = self::buildCreateTableSql($blueprint);
        self::$queryBuilder->rawQuery($sql);
    }

    public static function dropIfExists(string $table): void
    {
        $sql = sprintf('DROP TABLE IF EXISTS %s', $table);
        self::$queryBuilder->rawQuery($sql);
    }

    protected static function buildCreateTableSql(Blueprint $blueprint): string
    {
        $columns = [];
        foreach ($blueprint->getColumns() as $column) {
            $columns[] = self::buildColumnDefinition($column);
        }

        $primaryKey = $blueprint->getIndexes();
        if ($primaryKey) {
            $columns[] = sprintf('PRIMARY KEY (%s)', implode(', ', array_column($primaryKey, 'columns')));
        }

        $sql = sprintf(
            'CREATE TABLE %s (%s)',
            $blueprint->getTable(),
            implode(', ', $columns)
        );

        return $sql;
    }

    protected static function buildColumnDefinition(array $column): string
    {
        $columnSql = sprintf('%s %s', $column['name'], $column['type']);

        if (isset($column['parameters']['length'])) {
            $columnSql .= sprintf('(%d)', $column['parameters']['length']);
        }

        if (isset($column['modifiers'])) {
            foreach ($column['modifiers'] as $modifier) {
                if ($modifier['type'] === 'nullable') {
                    $columnSql .= ' NULL';
                } elseif ($modifier['type'] === 'default') {
                    $columnSql .= sprintf(' DEFAULT %s', $modifier['value']);
                } elseif ($modifier['type'] === 'unsigned') {
                    $columnSql .= ' UNSIGNED';
                } elseif ($modifier['type'] === 'autoIncrement') {
                    $columnSql .= ' AUTO_INCREMENT';
                } elseif ($modifier['type'] === 'unique') {
                    $columnSql .= ' UNIQUE';
                }
            }
        }

        return $columnSql;
    }
}