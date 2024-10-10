<?php

namespace Vest\Console;

use Exception;

/**
 * Kernel do console para gerenciar comandos.
 */
class ConsoleKernel
{
    protected $commands = [];

    /**
     * Registra um comando.
     *
     * @param Command $command
     * @return void
     */
    public function register(Command $command): void
    {
        $this->commands[$command->getSignature()] = $command;
    }

    /**
     * Manipula a entrada do console e retorna o comando apropriado.
     *
     * @param array $argv
     * @return Command
     * @throws Exception
     */
    public function handle(array $argv): Command
    {
        if (empty($argv[1])) {
            throw new Exception("No command specified.");
        }

        $commandSignature = $argv[1];
        if (!isset($this->commands[$commandSignature])) {
            throw new Exception("Command not found: $commandSignature");
        }

        return $this->commands[$commandSignature];
    }
}
