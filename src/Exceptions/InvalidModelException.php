<?php

namespace Vest\Exceptions;

use Exception;

class InvalidModelException extends BaseException
{
    public function __construct($message = "Modelo inválido", $errorCode = 'INVALID_MODEL', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $code, $previous);
    }
}