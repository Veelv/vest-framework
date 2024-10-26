<?php
namespace Vest\Debug;

trait LoggerAware
{
    protected ?Logger $logger = null;

    /**
     * Define o logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Obtém o logger
     */
    protected function getLogger(): Logger
    {
        if ($this->logger === null) {
            $this->logger = new Logger();
        }
        return $this->logger;
    }

    /**
     * Log helper methods
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->getLogger()->info($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->getLogger()->error($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->getLogger()->warning($message, $context);
    }

    protected function logDebug(string $message, array $context = []): void
    {
        $this->getLogger()->debug($message, $context);
    }
}