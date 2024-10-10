<?php

namespace Vest\Exceptions;

use Exception;

class DatabaseConnectionException extends BaseException
{
    public function __construct($message = "Erro de conexão com o banco de dados", $errorCode = 'DB_CONN_ERR', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $code, $previous);
    }
}