<?php

namespace Vest\Console;

class Brow
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void
    {
        $this->commands = [
            'list' => ListCommand::class,
            'make' => MakeCommand::class,
            'migration' => MigrationCommand::class,
            'seed' => SeedCommand::class,
            'frontend' => FrontendCommand::class
        ];
    }

    public function registerCommand(string $name, string $commandClass): void
    {
        $this->commands[$name] = $commandClass;
    }

    public function run(array $args): void
    {
        if (empty($args)) {
            $this->showHelp();
            return;
        }

        $commandName = $args[0];
        $commandArgs = array_slice($args, 1);

        if (!isset($this->commands[$commandName])) {
            echo "Comando não encontrado: $commandName\n";
            return;
        }

        $commandClass = $this->commands[$commandName];
        $command = new $commandClass();
        $command->execute($commandArgs);
    }

    private function showHelp(): void
    {
        echo "Uso: php brow <comando> [argumentos]\n\n";
        echo "Comandos disponíveis:\n";
        foreach ($this->commands as $name => $class) {
            echo "  $name\n";
        }
    }
}