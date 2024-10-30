<?php

namespace Vest\Exceptions;

use PDOException;

class DatabaseQueryException extends BaseException
{
    protected string $sql;
    protected array $bindings;
    protected array $errorInfo;

    public function __construct(
        string $message,  // Adicionado parâmetro de mensagem
        string $sql,
        array $bindings = [],
        ?PDOException $previous = null
    ) {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->errorInfo = $previous ? $previous->errorInfo : [];

        if ($previous) {
            $errorMessage = sprintf(
                "%s\nErro ao executar a consulta: %s\nCódigo de erro: %s\nDetalhe: %s\nSQL: %s\nParâmetros: %s",
                $message,
                $previous->getMessage(),
                $this->errorInfo[0] ?? 'N/A',
                $this->errorInfo[2] ?? 'N/A',
                $this->sql,
                json_encode($this->bindings, JSON_PRETTY_PRINT)
            );
        } else {
            $errorMessage = sprintf(
                "%s\nSQL: %s\nParâmetros: %s",
                $message,
                $this->sql,
                json_encode($this->bindings, JSON_PRETTY_PRINT)
            );
        }

        parent::__construct(
            $errorMessage, 
            'DB_QUERY_ERR', 
            (int) ($previous ? $previous->getCode() : 0), 
            $previous
        );
    }

    /**
     * Obtém a consulta SQL que causou o erro
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Obtém os parâmetros da consulta
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Obtém as informações detalhadas do erro
     */
    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}