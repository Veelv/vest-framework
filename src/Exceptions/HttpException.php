<?php

namespace Vest\Exceptions;

class HttpException extends BaseException
{
    protected int $statusCode; // Código de status HTTP

    public function __construct(int $statusCode, array $errors = [], string $errorCode = '', \Throwable $previous = null)
    {
        $this->statusCode = $statusCode; // Define o código de status HTTP

        // Converte o array de erros em uma string
        $message = implode(", ", $errors); 
        // Chama o construtor da classe pai
        parent::__construct($message, $errorCode, 0, $previous); 
    }

    public function getStatusCode(): int
    {
        return $this->statusCode; // Retorna o código de status
    }
}
