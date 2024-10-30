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

    public function string(string $column, int $length = 255): self
    {
        return $this->addColumn('VARCHAR', $column, ['length' => $length]);
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
    protected function addColumn(string $type, string $name, array $parameters = []): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'parameters' => $parameters,
            'modifiers' => []
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

    // Método toSql
    public function toSql(string $driver = 'mysql'): string
    {
        $columnDefinitions = [];
        $constraints = [];

        foreach ($this->columns as $column) {
            $def = $this->buildColumnDefinition($column, $driver);
            $columnDefinitions[] = $def;

            if ($driver === 'pgsql') {
                $constraints = array_merge($constraints, $this->buildConstraints($column));
            }
        }

        $sql = $this->buildCreateTableStatement(
            $this->table,
            array_merge($columnDefinitions, $constraints),
            $driver
        );

        return $sql;
    }

    protected function buildColumnDefinition(array $column, string $driver): string
    {
        $def = "`{$column['name']}` {$column['type']}";

        if ($column['type'] === 'VARCHAR' && isset($column['parameters']['length'])) {
            $def .= "({$column['parameters']['length']})";
        } elseif (isset($column['parameters']['total']) && isset($column['parameters']['places'])) {
            $def .= "({$column['parameters']['total']},{$column['parameters']['places']})";
        }

        foreach ($column['modifiers'] as $modifier) {
            $def .= $this->buildModifier($modifier, $driver);
        }
        
        return $def;
    }
    protected function mapColumnType(string $type, string $driver): string
    {
        $typeMap = [
            'mysql' => [
                'string' => 'VARCHAR',
                'text' => 'TEXT',
                'integer' => 'INT',
                'bigint' => 'BIGINT',
                'boolean' => 'tinyint',
                'date' => 'date',
                'dateTime' => 'datetime',
                'decimal' => 'decimal',
                'double' => 'double',
                'float' => 'float',
                'json' => 'json',
                'longText' => 'longtext',
                'mediumText' => 'mediumtext',
                'time' => 'time',
                'timestamp' => 'TIMESTAMP',
            ],
            'pgsql' => [
                'string' => 'VARCHAR',
                'text' => 'text',
                'integer' => 'integer',
                'bigInteger' => 'bigint',
                'boolean' => 'boolean',
                'date' => 'date',
                'dateTime' => 'timestamp',
                'decimal' => 'decimal',
                'double' => 'double precision',
                'float' => 'real',
                'json' => 'jsonb',
                'longText' => 'text',
                'mediumText' => 'text',
                'time' => 'time',
                'timestamp' => 'timestamp',
            ],
            'sqlite' => [
                'string' => 'VARCHAR',
                'text' => 'text',
                'integer' => 'integer',
                'bigInteger' => 'integer',
                'boolean' => 'integer',
                'date' => 'date',
                'dateTime' => 'datetime',
                'decimal' => 'real',
                'double' => 'real',
                'float' => 'real',
                'json' => 'text',
                'longText' => 'text',
                'mediumText' => 'text',
                'time' => 'time',
                'timestamp' => 'datetime',
            ],
            'sqlsrv' => [
                'string' => 'VARCHAR',
                'text' => 'nvarchar(max)',
                'integer' => 'int',
                'bigInteger' => 'bigint',
                'boolean' => 'bit',
                'date' => 'date',
                'dateTime' => 'datetime2',
                'decimal' => 'decimal',
                'double' => 'float',
                'float' => 'real',
                'json' => 'nvarchar(max)',
                'longText' => 'nvarchar(max)',
                'mediumText' => 'nvarchar(max)',
                'time' => 'time',
                'timestamp' => 'datetime2',
            ],
        ];

        $mappedType = $typeMap[$driver][$type] ?? $type;
        return $mappedType;
    }

    protected function buildModifier(array $modifier, string $driver): string
    {
        $result = '';
        switch ($modifier['type']) {
            case 'nullable':
                $result = ' NULL';
                break;
            case 'default':
                $result = " DEFAULT {$modifier['value']}";
                break;
            case 'unsigned':
                $result = ' UNSIGNED';
                break;
            case 'unique':
                $result = ' UNIQUE';
                break;
            case 'index':
                $result = ' INDEX';
                break;
            case 'primary':
                $result = ' PRIMARY KEY';
                break;
            case 'autoIncrement':
                $result = ' AUTO_INCREMENT';
                break;
            default:
                echo "Modificador desconhecido: {$modifier['type']}\n";
                break;
        }
        return $result;
    }

    protected function buildConstraints(array $column): array
    {
        $constraints = [];

        if (in_array('primary', array_column($column['modifiers'], 'type'))) {
            $constraints[] = "ALTER TABLE `{$this->table}` ADD CONSTRAINT `{$this->table}_pkey` PRIMARY KEY (`{$column['name']}`)";
        }

        if (in_array('unique', array_column($column['modifiers'], 'type'))) {
            $constraints[] = "ALTER TABLE `{$this->table}` ADD CONSTRAINT `{$this->table}_{$column['name']}_key` UNIQUE (`{$column['name']}`)";
        }

        if (in_array('index', array_column($column['modifiers'], 'type'))) {
            $constraints[] = "ALTER TABLE `{$this->table}` ADD INDEX `{$this->table}_{$column['name']}_index` (`{$column['name']}`)";
        }

        return $constraints;
    }

    protected function buildCreateTableStatement(string $table, array $definitions, string $driver): string
    {
        $sql = "CREATE TABLE `{$table}` (";

        $sql .= implode(', ', $definitions);

        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if ($driver === 'pgsql') {
            $sql .= " TABLESPACE pg_default";
        }

        $sql .= ";";

        return $sql;
    }
}
