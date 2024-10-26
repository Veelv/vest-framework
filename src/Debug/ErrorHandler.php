<?php

namespace Vest\Debug;

use ErrorException;
use Exception;
use Throwable;
use Vest\Exceptions\HttpException;
use Vest\Support\ViewFactory;

class ErrorHandler
{
    protected Logger $logger;
    protected bool $debug;
    protected ViewFactory $viewFactory;

    public function __construct(Logger $logger = null, bool $debug = false)
    {
        // Inicializa o logger e o modo de depuração
        $this->logger = $logger ?? new Logger();
        $this->debug = $debug;
        $this->viewFactory = new ViewFactory();
    }

    public function handleException(Throwable $exception)
    {
        // Registra a exceção
        $this->logger->error('Exception thrown', [
            'exception' => $exception,
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'params' => $_REQUEST,
            ],
            'stack_trace' => $exception->getTraceAsString(),
        ]);

        // Determina o código de status baseado na exceção
        $statusCode = $exception instanceof HttpException ? $exception->getStatusCode() : 500;

        // Configura o código de resposta HTTP
        http_response_code($statusCode);

        // Renderiza a página de erro em modo debug
        if ($this->debug) {
            echo $this->renderDebugPage($exception);
        } else {
            echo $this->renderProductionError();
        }

        exit; // Finaliza a execução após a resposta
    }
    public function register(): void
    {
        // Configura o tratamento de erros e exceções
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    protected function renderHttpException(Throwable $e): void
    {
        // Configura a resposta para 500
        http_response_code(500);

        // Renderiza uma página de erro dependendo do modo de depuração
        if (!$this->debug) {
            echo $this->renderProductionError();
            return;
        }

        echo $this->renderDebugPage($e);
    }

    protected function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        // Registra o erro
        $this->logger->error($message, ['file' => $file, 'line' => $line]);

        // Renderiza uma exceção HTTP
        $this->renderHttpException(new ErrorException($message, 0, $level, $file, $line));
    }

    protected function renderProductionError(): string
    {
        // Retorna um HTML simples para produção
        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro do Servidor</title>
            <style>
                body {
                    font-family: sans-serif;
                    background: #f7f7f7;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .error-container {
                    background: white;
                    padding: 2rem;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 500px;
                    width: 90%;
                }
                h1 { color: #e53e3e; margin-bottom: 1rem; }
                p { color: #4a5568; line-height: 1.6; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>500 | Erro do Servidor</h1>
                <p>Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.</p>
            </div>
        </body>
        </html>
        HTML;
    }
    protected function renderDebugPage(Throwable $e): string
    {
        $data = [
            'exceptionClass' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => nl2br(htmlspecialchars($e->getTraceAsString())),
            'requestData' => $this->arrayToTable($_GET, $_POST),
            'serverData' => $this->arrayToTable($_SERVER),
        ];

        return $this->viewFactory->make('debug.error', $data);
    }

    private function arrayToTable(array $data, array $otherData = []): string
    {
        $output = '';
        foreach ($data as $key => $value) {
            $output .= '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        if (!empty($otherData)) {
            foreach ($otherData as $key => $value) {
                $output .= '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
            }
        }
        return $output;
    }

    protected function getCodeSnippet(string $file, int $errorLine, int $linesAround = 10): string
    {
        if (!file_exists($file)) {
            return "// Arquivo não encontrado: {$file}";
        }

        // Lê o arquivo e obtém linhas ao redor do erro
        $lines = file($file);
        $snippetLines = array_slice($lines, max(0, $errorLine - $linesAround - 1), $linesAround * 2);
        return nl2br(htmlspecialchars(implode('', $snippetLines)));
    }

    protected function cleanServerVariables(array $server): array
    {
        // Remove informações sensíveis de $_SERVER
        unset($server['HTTP_AUTHORIZATION'], $server['REMOTE_ADDR']);
        return $server;
    }

    protected function handleShutdown(): void
    {
        // Captura erros fatais
        $error = error_get_last();
        if ($error) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
