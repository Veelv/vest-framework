<?php

namespace Vest\ORM;

use Vest\Exceptions\QueryBuilderException;

class Schema
{
    protected QueryBuilder $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Cria uma nova tabela no banco de dados.
     *
     * @param string $table
     * @param callable $callback
     * @throws QueryBuilderException
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint); // Configura as colunas e restrições através da Blueprint

        $sql = $this->buildCreateTableSql($blueprint);
        $this->queryBuilder->rawQuery($sql);
    }

    /**
     * Gera o SQL para criar uma tabela.
     *
     * @param Blueprint $blueprint
     * @return string
     */
    protected function buildCreateTableSql(Blueprint $blueprint): string
    {
        $columns = [];
        foreach ($blueprint->getColumns() as $column) {
            $columns[] = $this->buildColumnDefinition($column);
        }

        $primaryKey = $blueprint->getPrimaryKey();
        if ($primaryKey) {
            $columns[] = sprintf('PRIMARY KEY (%s)', implode(', ', $primaryKey));
        }

        $foreignKeys = $this->buildForeignKeys($blueprint);
        if (!empty($foreignKeys)) {
            $columns = array_merge($columns, $foreignKeys);
        }

        $sql = sprintf(
            'CREATE TABLE %s (%s)',
            $blueprint->getTable(),
            implode(', ', $columns)
        );

        return $sql;
    }

    /**
     * Define o SQL de uma coluna individual.
     *
     * @param array $column
     * @return string
     */
    protected function buildColumnDefinition(array $column): string
    {
        $sql = sprintf(
            '%s %s%s%s%s',
            $column['name'],
            $column['type'],
            $column['unsigned'] ? ' UNSIGNED' : '',
            $column['nullable'] ? ' NULL' : ' NOT NULL',
            isset($column['default']) ? ' DEFAULT ' . $column['default'] : ''
        );

        if ($column['autoIncrement']) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }

    /**
     * Gera as chaves estrangeiras.
     *
     * @param Blueprint $blueprint
     * @return array
     */
    protected function buildForeignKeys(Blueprint $blueprint): array
    {
        $foreignKeys = [];
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $foreignKeys[] = sprintf(
                'FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s ON UPDATE %s',
                $foreignKey['column'],
                $foreignKey['references_table'],
                $foreignKey['references_column'],
                $foreignKey['onDelete'],
                $foreignKey['onUpdate']
            );
        }

        return $foreignKeys;
    }

    /**
     * Altera uma tabela existente.
     *
     * @param string $table
     * @param callable $callback
     * @throws QueryBuilderException
     */
    public function alter(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint); // Configura colunas a serem alteradas

        $sql = $this->buildAlterTableSql($blueprint);
        $this->queryBuilder->rawQuery($sql);
    }

    /**
     * Gera o SQL para alterar uma tabela.
     *
     * @param Blueprint $blueprint
     * @return string
     */
    protected function buildAlterTableSql(Blueprint $blueprint): string
    {
        $columns = [];
        foreach ($blueprint->getColumns() as $column) {
            $columns[] = sprintf('ADD %s', $this->buildColumnDefinition($column));
        }

        $sql = sprintf(
            'ALTER TABLE %s %s',
            $blueprint->getTable(),
            implode(', ', $columns)
        );

        return $sql;
    }

    /**
     * Remove uma tabela do banco de dados.
     *
     * @param string $table
     * @throws QueryBuilderException
     */
    public function drop(string $table): void
    {
        $sql = sprintf('DROP TABLE IF EXISTS %s', $table);
        $this->queryBuilder->rawQuery($sql);
    }
}

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $primaryKey = [];
    protected array $foreignKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    // Tipos de coluna comuns

    public function string(string $name, int $length = 255): self
    {
        $this->addColumn($name, 'VARCHAR(' . $length . ')');
        return $this;
    }

    public function integer(string $name): self
    {
        $this->addColumn($name, 'INT');
        return $this;
    }

    public function bigInteger(string $name): self
    {
        $this->addColumn($name, 'BIGINT');
        return $this;
    }

    public function boolean(string $name): self
    {
        $this->addColumn($name, 'BOOLEAN');
        return $this;
    }

    public function text(string $name): self
    {
        $this->addColumn($name, 'TEXT');
        return $this;
    }

    public function json(string $name): self
    {
        $this->addColumn($name, 'JSON');
        return $this;
    }

    public function timestamp(string $name): self
    {
        $this->addColumn($name, 'TIMESTAMP');
        return $this;
    }

    // Opções para colunas

    public function nullable(bool $nullable = true): self
    {
        $lastColumnKey = array_key_last($this->columns);
        if ($lastColumnKey !== null) {
            $this->columns[$lastColumnKey]['nullable'] = $nullable;
        }
        return $this;
    }

    public function unsigned(): self
    {
        $lastColumnKey = array_key_last($this->columns);
        if ($lastColumnKey !== null) {
            $this->columns[$lastColumnKey]['unsigned'] = true;
        }
        return $this;
    }

    public function autoIncrement(): self
    {
        $lastColumnKey = array_key_last($this->columns);
        if ($lastColumnKey !== null) {
            $this->columns[$lastColumnKey]['autoIncrement'] = true;
        }
        return $this;
    }

    public function default($value): self
    {
        $lastColumnKey = array_key_last($this->columns);
        if ($lastColumnKey !== null) {
            $this->columns[$lastColumnKey]['default'] = $value;
        }
        return $this;
    }

    // Chave primária e estrangeira

    public function primary(string ...$columns): self
    {
        $this->primaryKey = $columns;
        return $this;
    }

    public function foreign(string $column): ForeignKey
    {
        return new ForeignKey($this, $column);
    }

    protected function addColumn(string $name, string $type): void
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'nullable' => false,
            'unsigned' => false,
            'autoIncrement' => false,
            'default' => null,
        ];
    }

    public function addForeignKey(array $foreignKey): void
    {
        $this->foreignKeys[] = $foreignKey;
    }
}

class ForeignKey
{
    protected Blueprint $blueprint;
    protected string $column;
    protected string $referencesTable;
    protected string $referencesColumn;
    protected string $onDelete = 'RESTRICT';
    protected string $onUpdate = 'RESTRICT';

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->referencesColumn = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencesTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function add(): void
    {
        $this->blueprint->addForeignKey([
            'column' => $this->column,
            'references_table' => $this->referencesTable,
            'references_column' => $this->referencesColumn,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate,
        ]);
    }
}