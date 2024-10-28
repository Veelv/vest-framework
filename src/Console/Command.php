<?php
namespace Vest\Console;
abstract class Command {
    protected $name;
    protected $description;
    protected $arguments = [];
    protected $options = [];

    abstract public function execute(array $args): void;

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    protected function parseArgs(array $args): array {
        $parsed = ['args' => [], 'options' => []];
        
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                $option = substr($arg, 2);
                if (strpos($option, '=') !== false) {
                    list($key, $value) = explode('=', $option, 2);
                    $parsed['options'][$key] = $value;
                } else {
                    $parsed['options'][$option] = true;
                }
            } else {
                $parsed['args'][] = $arg;
            }
        }

        return $parsed;
    }
}