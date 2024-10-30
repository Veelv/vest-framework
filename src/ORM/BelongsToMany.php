<?php

namespace Vest\ORM;

use PDO;
use Vest\Exceptions\RelationshipException;

class BelongsToMany
{
    protected BaseModel $parent;
    protected string $related;
    protected string $table;
    protected string $foreignKey;
    protected string $otherKey;
    protected QueryBuilder $query;

    public function __construct(
        BaseModel $parent,
        string $related,
        string $table,
        string $foreignKey,
        string $otherKey,
        PDO $connection
    ) {
        // Validação da classe relacionada
        if (!class_exists($related)) {
            throw new RelationshipException("Classe relacionada '$related' não existe");
        }

        // Validação dos parâmetros
        $this->validateParameters($table, $foreignKey, $otherKey);

        $this->parent = $parent;
        $this->related = $related;
        $this->table = $this->sanitizeTableName($table);
        $this->foreignKey = $this->sanitizeColumnName($foreignKey);
        $this->otherKey = $this->sanitizeColumnName($otherKey);
        $this->query = new QueryBuilder($connection);
    }

    /**
     * Valida os parâmetros da relação
     */
    protected function validateParameters(string $table, string $foreignKey, string $otherKey): void 
    {
        if (empty($table)) {
            throw new RelationshipException("Nome da tabela não pode estar vazio");
        }

        if (empty($foreignKey) || empty($otherKey)) {
            throw new RelationshipException("Chaves estrangeiras não podem estar vazias");
        }

        // Validação de caracteres permitidos
        $pattern = '/^[a-zA-Z0-9_]+$/';
        if (!preg_match($pattern, $table) || 
            !preg_match($pattern, $foreignKey) || 
            !preg_match($pattern, $otherKey)) {
            throw new RelationshipException("Nomes de tabela e colunas devem conter apenas letras, números e underscore");
        }
    }

    /**
     * Sanitiza nome de tabela
     */
    protected function sanitizeTableName(string $table): string 
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    }

    /**
     * Sanitiza nome de coluna
     */
    protected function sanitizeColumnName(string $column): string 
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    }

    /**
     * Valida ID antes de operações
     */
    protected function validateId($id): void 
    {
        if (!is_numeric($id) && !is_string($id)) {
            throw new RelationshipException("ID inválido fornecido");
        }
    }

    public function get(): array
    {
        try {
            $relatedModel = new $this->related();
            $relatedTable = $relatedModel->getTable();
            
            return $this->query->table($this->table)
                ->select(["$relatedTable.*"])
                ->join(
                    $relatedTable,
                    "$relatedTable.{$relatedModel->getPrimaryKey()}",
                    '=',
                    "{$this->table}.{$this->otherKey}"
                )
                ->where(
                    "{$this->table}.{$this->foreignKey}", 
                    '=', 
                    $this->parent->toArray()[$this->parent->getPrimaryKey()]
                )
                ->get();
        } catch (\Exception $e) {
            throw new RelationshipException(
                "Erro ao recuperar registros relacionados: " . $e->getMessage()
            );
        }
    }

    public function attach($id): bool
    {
        try {
            $this->validateId($id);

            // Verifica se a relação já existe
            $existing = $this->query->table($this->table)
                ->where($this->foreignKey, '=', $this->parent->toArray()[$this->parent->getPrimaryKey()])
                ->where($this->otherKey, '=', $id)
                ->get();

            if (!empty($existing)) {
                throw new RelationshipException("Relação já existe");
            }

            return $this->query->table($this->table)->insert([
                $this->foreignKey => $this->parent->toArray()[$this->parent->getPrimaryKey()],
                $this->otherKey => $id
            ]);
        } catch (\Exception $e) {
            throw new RelationshipException(
                "Erro ao criar relação: " . $e->getMessage()
            );
        }
    }

    public function detach($id = null): bool
    {
        try {
            $this->query->table($this->table)
                ->where(
                    $this->foreignKey, 
                    '=', 
                    $this->parent->toArray()[$this->parent->getPrimaryKey()]
                );

            if ($id !== null) {
                $this->validateId($id);
                $this->query->where($this->otherKey, '=', $id);
            }

            return $this->query->delete();
        } catch (\Exception $e) {
            throw new RelationshipException(
                "Erro ao remover relação: " . $e->getMessage()
            );
        }
    }

    /**
     * Verifica se uma relação existe
     */
    public function exists($id): bool 
    {
        try {
            $this->validateId($id);
            
            $result = $this->query->table($this->table)
                ->where($this->foreignKey, '=', $this->parent->toArray()[$this->parent->getPrimaryKey()])
                ->where($this->otherKey, '=', $id)
                ->get();

            return !empty($result);
        } catch (\Exception $e) {
            throw new RelationshipException(
                "Erro ao verificar relação: " . $e->getMessage()
            );
        }
    }
}