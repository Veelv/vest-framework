<?php

namespace Vest\ORM\Schema;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreign = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // Tipos de Colunas Básicos
    public function id($column = 'id'): self
    {
        return $this->bigIncrements($column);
    }

    public function uuid($column = 'id'): self
    {
        return $this->string($column, 36)->primary();
    }

    public function bigIncrements($column): self
    {
        return $this->unsignedBigInteger($column, true);
    }

    public function unsignedBigInteger($column, $autoIncrement = false): self
    {
        $column = $this->addColumn('bigint', $column);
        $this->unsigned();

        if ($autoIncrement) {
            $this->addModifier('autoIncrement');
            $this->primary();
        }

        return $this;
    }

    public function bigInteger($column): self
    {
        return $this->addColumn('bigint', $column);
    }

    public function binary($column): self
    {
        return $this->addColumn('blob', $column);
    }

    public function boolean($column): self
    {
        return $this->addColumn('boolean', $column);
    }

    public function char($column, $length = 255): self
    {
        return $this->addColumn('char', $column, ['length' => $length]);
    }

    public function date($column): self
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime($column): self
    {
        return $this->addColumn('datetime', $column);
    }

    public function decimal($column, $total = 8, $places = 2): self
    {
        return $this->addColumn('decimal', $column, [
            'total' => $total,
            'places' => $places,
        ]);
    }

    public function double($column, $total = null, $places = null): self
    {
        return $this->addColumn('double', $column, [
            'total' => $total,
            'places' => $places,
        ]);
    }

    public function enum($column, array $allowed): self
    {
        return $this->addColumn('enum', $column, [
            'allowed' => $allowed,
        ]);
    }

    public function float($column, $total = 8, $places = 2): self
    {
        return $this->addColumn('float', $column, [
            'total' => $total,
            'places' => $places,
        ]);
    }

    public function integer($column): self
    {
        return $this->addColumn('integer', $column);
    }

    public function json($column): self
    {
        return $this->addColumn('json', $column);
    }

    public function longText($column): self
    {
        return $this->addColumn('longtext', $column);
    }

    public function mediumText($column): self
    {
        return $this->addColumn('mediumtext', $column);
    }

    public function morphs($name): self
    {
        $this->string("{$name}_type");
        $this->unsignedBigInteger("{$name}_id");
        $this->index(["{$name}_type", "{$name}_id"]);

        return $this;
    }

    public function string($column, $length = 255): self
    {
        return $this->addColumn('string', $column, ['length' => $length]);
    }

    public function text($column): self
    {
        return $this->addColumn('text', $column);
    }

    public function time($column): self
    {
        return $this->addColumn('time', $column);
    }

    public function timestamp($column): self
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();

        return $this;
    }

    public function softDeletes(): self
    {
        return $this->timestamp('deleted_at')->nullable();
    }

    // Modificadores de Coluna
    public function nullable(): self
    {
        $this->addModifier('nullable');
        return $this;
    }

    public function default($value): self
    {
        $this->addModifier('default', $value);
        return $this;
    }

    public function unsigned(): self
    {
        $this->addModifier('unsigned');
        return $this;
    }

    public function unique(): self
    {
        $this->addModifier('unique');
        return $this;
    }

    public function index(): self
    {
        $this->addModifier('index');
        return $this;
    }

    public function primary(): self
    {
        $this->addModifier('primary');
        return $this;
    }

    // Chaves Estrangeiras
    public function foreign($columns): ForeignKeyDefinition
    {
        return new ForeignKeyDefinition($this, $columns);
    }

    // Métodos Internos
    protected function addColumn($type, $name, array $parameters = []): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'parameters' => $parameters,
            'modifiers' => [],
        ];

        return $this;
    }

    protected function addModifier($type, $value = null): void
    {
        $lastColumn = &$this->columns[count($this->columns) - 1];
        $lastColumn['modifiers'][] = [
            'type' => $type,
            'value' => $value,
        ];
    }

    // Getters
    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreign;
    }
}
