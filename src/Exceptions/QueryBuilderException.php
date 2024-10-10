<?php

namespace Vest\Exceptions;

use Exception;

class QueryBuilderException extends BaseException
{
    public function __construct($message = "Erro no construtor de consultas", $errorCode = 'QUERY_BUILDER_ERR', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $code, $previous);
    }
}