<?php

namespace Vest\Console;

use RuntimeException;

/**
 * Gerencia a coleção de comandos.
 */
class CommandCollection
{
    protected $commands = [];

    /**
     * Adiciona um comando à coleção.
     *
     * @param Command $command
     * @return $this
     */
    public function add(Command $command): self
    {
        $this->commands[$command->getSignature()] = $command;
        return $this;
    }

    /**
     * Obtém um comando pela assinatura.
     *
     * @param string $signature
     * @return Command
     * @throws RuntimeException
     */
    public function get(string $signature): Command
    {
        if (!isset($this->commands[$signature])) {
            throw new RuntimeException("Command not found: $signature");
        }
        return $this->commands[$signature];
    }

    /**
     * Retorna todos os comandos.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->commands;
    }
}
