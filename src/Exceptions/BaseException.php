<?php

namespace Vest\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    protected string $errorCode; // Código de erro específico

    public function __construct(string $message, string $errorCode = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode; // Armazena o código de erro
    }

    /**
     * Obtém o código de erro específico.
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Obtém uma representação legível do erro.
     * 
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "[%s]: %s (Code: %s)",
            get_class($this),
            $this->message,
            $this->getErrorCode()
        );
    }
}