<?php
namespace Vest\Exceptions;

class HttpException extends BaseException
{
    protected int $statusCode; // Código de status HTTP

    public function __construct(int $statusCode, string $message = "", string $errorCode = '', \Throwable $previous = null)
    {
        $this->statusCode = $statusCode; // Define o código de status HTTP
        parent::__construct($message, $errorCode, $statusCode, $previous); // Chama o construtor da classe pai
    }

    public function getStatusCode(): int
    {
        return $this->statusCode; // Retorna o código de status
    }
}