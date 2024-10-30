<?php

namespace Vest\Console;

class MakeCommand extends Command
{
    protected $name = 'make';
    protected $description = 'Creates a new component';

    private $makers = [];

    public function __construct()
    {
        $this->registerMakers();
    }

    private function registerMakers(): void
    {
        $this->makers = [
            'controller' => new Makers\ControllerMaker(),
            'model' => new Makers\ModelMaker(),
            'middleware' => new Makers\MiddlewareMaker(),
            'seeder' => new Makers\SeederMaker(),
            'routes' => new Makers\RoutesMaker(),
            'validation' => new Makers\ValidationMaker(),
        ];
    }

    public function execute(array $args): void
    {
        if (count($args) < 2) {
            $this->showHelp();
            return;
        }

        list($type, $name) = $args;
        $options = array_slice($args, 2);

        if (!isset($this->makers[$type])) {
            throw new \InvalidArgumentException("Invalid type: $type");
        }

        try {
            $this->makers[$type]->make($name, $options);
            echo "Successfully created $type: $name\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    private function showHelp(): void
    {
        echo "Usage: make <type> <name> [options]\n";
        echo "Available types: " . implode(', ', array_keys($this ->makers)) . "\n";
        echo "Options:\n";
        echo "  --api  Create API controller/routes\n";
    }
}