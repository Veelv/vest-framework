<?php

namespace Vest\Exceptions;

use Exception;

class TransactionException extends BaseException
{
    public function __construct($message = "Erro durante a transação", $errorCode = 'TRANSACTION_ERR', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $code, $previous);
    }
}