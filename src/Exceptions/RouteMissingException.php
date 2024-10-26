<?php

namespace Vest\Exceptions;

use Vest\Debug\Log;

class RouteMissingException extends \Exception
{
    private int $errorCode;

    public function __construct(string $message, int $code = 404)
    {
        parent::__construct($message, $code);
        $this->errorCode = $code;

        // Loga a mensagem de erro quando a exceção é criada
        $this->logError($message);
    }

    private function logError(string $message): void
    {
        $logger = Log::getInstance();
        $logger->error("RouteMissingException: $message", [
            'error_code' => $this->errorCode,
        ]);
    }
}