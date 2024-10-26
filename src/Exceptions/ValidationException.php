<?php

namespace Vest\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected array $errors;

    public function __construct(array $errors)
    {
        parent::__construct("Erro de validação.");
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
