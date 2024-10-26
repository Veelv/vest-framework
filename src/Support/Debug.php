<?php

namespace Vest\Support;
use Vest\Debug\Log;
use Vest\EnvLoader;

class Debug
{
    public function display($e, $message)
    {
        $result = [
            "message" => $message,
        ];

        // Obtém o ambiente
        $environment = EnvLoader::getenv("MODE");

        // Registra o erro no logger
        $logger = Log::getInstance();
        $logger->error("Unexpected exception: " . $e->getMessage(), [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString(),
        ]);

        // Em ambiente de desenvolvimento, adiciona detalhes ao resultado
        if ($environment === "development") {
            $result["code"] = $e->getCode();
            $result["error"] = $e->getMessage();
            $result["file"] = $e->getFile();
            $result["line"] = $e->getLine();
            $result["trace"] = $e->getTrace();

            $previous = $e->getPrevious();
            if ($previous) {
                echo "Previous exception chained:\n";
                echo $previous->getMessage() . "\n";
                echo $previous->getCode() . "\n";
            }
        }

        return $result;
    }
}
