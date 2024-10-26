<?php

namespace Vest\Debug;

use DateTime;

/**
 * Class Logger
 * 
 * Sistema de logs inspirado no Laravel.
 */
class Logger
{
    // Níveis de log disponíveis
    public const EMERGENCY = 'emergency';
    public const ALERT     = 'alert';
    public const CRITICAL  = 'critical';
    public const ERROR     = 'error';
    public const WARNING   = 'warning';
    public const NOTICE    = 'notice';
    public const INFO      = 'info';
    public const DEBUG     = 'debug';

    protected string $logPath;
    protected string $defaultChannel;
    protected array $channels;
    protected array $levelColors = [
        self::EMERGENCY => "\033[41m", // Vermelho Background
        self::ALERT     => "\033[31m", // Vermelho
        self::CRITICAL  => "\033[35m", // Magenta
        self::ERROR     => "\033[31m", // Vermelho
        self::WARNING   => "\033[33m", // Amarelo
        self::NOTICE    => "\033[36m", // Ciano
        self::INFO      => "\033[32m", // Verde
        self::DEBUG     => "\033[34m", // Azul
    ];

    /**
     * Construtor do Logger
     */
    public function __construct(string $logPath = null, string $defaultChannel = 'debug')
    {
        $this->logPath = $logPath ?? APP_PATH . '/init/logs';
        $this->defaultChannel = $defaultChannel;
        $this->channels = [];

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
        $this->info('Logger initialized', []);
    }

    /**
     * Cria um novo canal de log
     */
    public function channel(string $channel): self
    {
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new self($this->logPath, $channel);
        }
        return $this->channels[$channel];
    }

    /**
     * Log para nível emergency
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log para nível alert
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log para nível critical
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log para nível error
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log para nível warning
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log para nível notice
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log para nível info
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log para nível debug
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Método principal de logging
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = new DateTime();
        $logFile = $this->getLogFile();
    
        // Enriquece o contexto com informações adicionais
        $context = $this->enrichContext($context);
        
        // Adiciona a pilha de chamadas ao contexto
        $context['stack_trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    
        // Formata a mensagem de log
        $formattedMessage = $this->formatMessage(
            $date,
            $level,
            $message,
            $context
        );
    
        // Escreve a mensagem no arquivo de log
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    /**
     * Enriquece o contexto com informações adicionais
     */
    protected function enrichContext(array $context): array
    {
        return array_merge([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_id' => uniqid(),
            'session_id' => session_id() ?? 'No Session',
            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown URL',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown Method',
        ], $context);
    }
    

    /**
     * Formata a mensagem de log
     */
    protected function formatMessage(DateTime $date, string $level, string $message, array $context): string
    {
        $color = $this->levelColors[$level] ?? "";
        $reset = "\033[0m";

        $output = sprintf(
            "[%s] %s%s%s: %s",
            $date->format('Y-m-d H:i:s'),
            $color,
            strtoupper($level),
            $reset,
            $message
        );

        if (!empty($context)) {
            $output .= PHP_EOL . json_encode($context, JSON_PRETTY_PRINT);
        }

        return $output . PHP_EOL . PHP_EOL;
    }

    /**
     * Obtém o caminho do arquivo de log
     */
    public function getLogFile(): string
    {
        $date = date('Y-m-d');
        return sprintf(
            '%s/%s-%s.log',
            $this->logPath,
            $this->defaultChannel,
            $date
        );
    }

    /**
     * Limpa logs antigos
     */
    public function clean(int $days = 7): void
    {
        $files = glob($this->logPath . '/*.log');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $days * 24 * 60 * 60) {
                    unlink($file);
                }
            }
        }
    }
}