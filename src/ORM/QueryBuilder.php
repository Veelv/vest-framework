<?php

namespace Vest\ORM;

use PDO;
use Vest\Exceptions\DatabaseQueryException;
use Vest\Exceptions\QueryBuilderException;

class QueryBuilder
{
    protected PDO $connection;
    protected string $table;
    protected array $fields = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected ?string $orderBy = null;
    protected ?int $limit = null;
    protected ?int $offset = null;

    /**
     * Construtor do QueryBuilder.
     *
     * @param PDO $connection Conexão com o banco de dados.
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Define a tabela alvo da consulta.
     *
     * @param string $table Nome da tabela.
     * @return $this
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Define os campos a serem selecionados.
     *
     * @param array $fields Campos a serem retornados.
     * @return $this
     */
    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Adiciona condições WHERE à consulta.
     *
     * @param string $field Campo da condição.
     * @param string $operator Operador de comparação (=, <, >, etc.).
     * @param mixed $value Valor a ser comparado.
     * @return $this
     */
    public function where(string $field, string $operator, $value): self
    {
        $this->wheres[] = "$field $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Adiciona uma cláusula ORDER BY à consulta.
     *
     * @param string $field Campo a ser ordenado.
     * @param string $direction Direção da ordenação (ASC ou DESC).
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy = "$field $direction";
        return $this;
    }

    /**
     * Define o limite de registros a serem retornados.
     *
     * @param int $limit Número máximo de registros.
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Define o offset para a consulta.
     *
     * @param int $offset Deslocamento.
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Constrói a consulta SQL.
     *
     * @return string
     * @throws QueryBuilderException
     */
    protected function buildQuery(): string
    {
        if (empty($this->table)) {
            throw new QueryBuilderException("Tabela não definida.");
        }

        $sql = sprintf("SELECT %s FROM %s", implode(", ", $this->fields), $this->table);

        if ($this->wheres) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY " . $this->orderBy;
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    /**
     * Executa a consulta e retorna os resultados.
     *
     * @return array
     * @throws DatabaseQueryException
     * @throws QueryBuilderException
     */
    public function get(): array
    {
        try {
            $sql = $this->buildQuery();
            $statement = $this->connection->prepare($sql);
            $statement->execute($this->bindings);

            return $statement->fetchAll();
        } catch (\PDOException $e) {
            throw new DatabaseQueryException($sql, $this->bindings, "Erro ao executar a consulta", 'DB_QUERY_ERR', $e->getCode(), $e);
        }
    }

    /**
     * Executa uma consulta SQL crua.
     *
     * @param string $sql A consulta SQL.
     * @param array $bindings Parâmetros para a consulta.
     * @return array
     * @throws DatabaseQueryException
     */
    public function rawQuery(string $sql, array $bindings = []): array
    {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($bindings);

            return $statement->fetchAll();
        } catch (\PDOException $e) {
            throw new DatabaseQueryException($sql, $bindings, "Erro na consulta bruta", 'DB_QUERY_ERR', $e->getCode(), $e);
        }
    }

    /**
     * Insere um novo registro na tabela.
     *
     * @param array $data Dados a serem inseridos [campo => valor].
     * @return bool
     * @throws DatabaseQueryException
     */
    public function insert(array $data): bool
    {
        try {
            $fields = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), '?'));
            $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $fields, $placeholders);
            
            $statement = $this->connection->prepare($sql);
            return $statement->execute(array_values($data));
        } catch (\PDOException $e) {
            throw new DatabaseQueryException($sql, array_values($data), "Erro ao inserir registro", 'DB_INSERT_ERR', $e->getCode(), $e);
        }
    }

    /**
     * Atualiza registros na tabela.
     *
     * @param array $data Dados a serem atualizados [campo => valor].
     * @return bool
     * @throws DatabaseQueryException
     * @throws QueryBuilderException
     */
    public function update(array $data): bool
    {
        if (empty($this->wheres)) {
            throw new QueryBuilderException("Atualização sem condição WHERE não é permitida.");
        }

        try {
            $fields = implode(" = ?, ", array_keys($data)) . " = ?";
            $sql = sprintf("UPDATE %s SET %s WHERE %s", $this->table, $fields, implode(" AND ", $this->wheres));

            $statement = $this->connection->prepare($sql);
            return $statement->execute(array_merge(array_values($data), $this->bindings));
        } catch (\PDOException $e) {
            throw new DatabaseQueryException($sql, array_merge(array_values($data), $this->bindings), "Erro ao atualizar registros", 'DB_UPDATE_ERR', $e->getCode(), $e);
        }
    }

    /**
     * Remove registros da tabela.
     *
     * @return bool
     * @throws DatabaseQueryException
     * @throws QueryBuilderException
     */
    public function delete(): bool
    {
        if (empty($this->wheres)) {
            throw new QueryBuilderException("Exclusão sem condição WHERE não é permitida.");
        }

        try {
            $sql = sprintf("DELETE FROM %s WHERE %s", $this->table, implode(" AND ", $this->wheres));
            $statement = $this->connection->prepare($sql);
            return $statement->execute($this->bindings);
        } catch (\PDOException $e) {
            throw new DatabaseQueryException($sql, $this->bindings, "Erro ao excluir registros", 'DB_DELETE_ERR', $e->getCode(), $e);
        }
    }
}