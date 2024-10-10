<?php

namespace Vest\Exceptions;

use Exception;

class DatabaseQueryException extends BaseException
{
    protected string $sql;
    protected array $bindings;

    public function __construct(string $sql, array $bindings = [], $message = "Erro na execução da consulta", $errorCode = 'DB_QUERY_ERR', int $code = 0, Exception $previous = null)
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        parent::__construct($message, $errorCode, $code, $previous);
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function __toString(): string
    {
        return parent::__toString() . sprintf(" (SQL: %s, Bindings: %s)", $this->sql, json_encode($this->bindings));
    }
}