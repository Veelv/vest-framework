<?php 

namespace Vest\Database;

use Vest\ORM\Schema;
use Vest\ORM\Connection;
use Vest\ORM\QueryBuilder;
use Vest\Support\Str;

abstract class Migration
{
    protected Schema $schema;
    protected string $table;

    /**
     * Construtor da classe Migration.
     *
     * @param Connection $connection Instância da classe Connection.
     */
    public function __construct(Connection $connection)
    {
        // Cria uma instância de QueryBuilder a partir da conexão
        $queryBuilder = new QueryBuilder($connection->getConnection());
        $this->schema = new Schema($queryBuilder);
    }

    /**
     * Método que deve ser implementado para aplicar a migração.
     */
    abstract public function up(): void;

    /**
     * Método que deve ser implementado para reverter a migração.
     */
    abstract public function down(): void;

    /**
     * Cria uma nova tabela.
     *
     * @param string $table Nome da tabela a ser criada.
     * @param callable $callback Função de callback para definir a estrutura da tabela.
     */
    protected function create(string $table, callable $callback): void
    {
        $this->table = $table;
        $this->schema->create($table, $callback);
    }

    /**
     * Altera uma tabela existente.
     *
     * @param string $table Nome da tabela a ser alterada.
     * @param callable $callback Função de callback para definir as alterações na tabela.
     */
    protected function table(string $table, callable $callback): void
    {
        $this->table = $table;
        $this->schema->alter($table, $callback);
    }

    /**
     * Remove uma tabela.
     *
     * @param string $table Nome da tabela a ser removida.
     */
    protected function drop(string $table): void
    {
        $this->schema->drop($table);
    }

    /**
     * Adiciona as colunas created_at e updated_at à tabela atual.
     */
    protected function addTimestamps(): void
    {
        $this->schema->alter($this->table, function ($table) {
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Adiciona a coluna deleted_at para soft deletes à tabela atual.
     */
    protected function addSoftDeletes(): void
    {
        $this->schema->alter($this->table, function ($table) {
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Adiciona colunas para morfismos à tabela atual.
     *
     * @param string $name Nome do morfismo.
     */
    protected function morphs(string $name): void
    {
        $this->schema->alter($this->table, function ($table) use ($name) {
            $table->unsignedBigInteger("{$name}_id");
            $table->string("{$name}_type");
            $table->index(["{$name}_id", "{$name}_type"], "idx_" . Str::snake($name) . "_morphs");
        });
    }
}