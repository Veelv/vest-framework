<?php

namespace Vest\Exceptions;

use Exception;
use PDOException; // Adicione isso se não estiver presente

class DatabaseQueryException extends BaseException
{
    protected string $sql;
    protected array $bindings;
    protected array $errorInfo;

    public function __construct(
        string $sql,
        array $bindings = [],
        ?PDOException $previous = null
    ) {
        $this->sql = $sql;
        $this->bindings = $bindings;

        if ($previous) {
            $this->errorInfo = $previous->errorInfo;
            $message = sprintf(
                "Erro ao executar a consulta: %s (Código de erro: %s, Tabela: %s, Coluna: %s, Valor: %s)",
                $previous->getMessage(),
                $this->errorInfo[0],
                $this->errorInfo[2],
                $this->errorInfo[1],
                json_encode($this->bindings)
            );
        } else {
            $message = "Erro na execução da consulta";
        }

        parent::__construct($message, 'DB_QUERY_ERR', (int) ($previous ? $previous->getCode() : 0), $previous);
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}