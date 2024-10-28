<?php
namespace Vest\Console;
class CommandCollection {
    private $commands = [];

    public function add(Command $command): void {
        $this->commands[$command->getName()] = $command;
    }

    public function get(string $name): ?Command {
        return $this->commands[$name] ?? null;
    }

    public function all(): array {
        return $this->commands;
    }
    
    public function exists(string $name): bool {
        return isset($this->commands[$name]);
    }
}