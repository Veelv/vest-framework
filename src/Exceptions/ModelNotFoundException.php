<?php

namespace Vest\Exceptions;

use Exception;

class ModelNotFoundException extends BaseException
{
    public function __construct($message = "Modelo não encontrado", $errorCode = 'MODEL_NOT_FOUND', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $code, $previous);
    }
}