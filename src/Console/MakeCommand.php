<?php

namespace Vest\Console;

class MakeCommand extends Command
{
    protected $name = 'rg';
    protected $description = 'Creates a new component';

    private $types = [
        'controller',
        'rest-controller',
        'model',
        'middleware',
        'migration',
        'seeder'
    ];

    private $stubPath;

    public function __construct()
    {
        $this->stubPath = __DIR__ . '/stubs/';
    }

    public function execute(array $args): void
    {
        $parsed = $this->parseArgs($args);

        if (count($parsed['args']) < 2) {
            throw new \InvalidArgumentException(
                "Usage: make <type> <name> [--api] [--force]\n" .
                    "Available types: " . implode(', ', $this->types)
            );
        }

        list($type, $name) = $parsed['args'];
        $force = isset($parsed['options']['force']);
        $isApi = isset($parsed['options']['api']);

        // If it's a controller and the --api flag is set, use the rest-controller
        if ($type === 'controller' && $isApi) {
            $type = 'rest-controller';
        }

        if (!in_array($type, $this->types)) {
            throw new \InvalidArgumentException(
                "Invalid type. Available types: " . implode(', ', $this->types)
            );
        }

        $this->makeComponent($type, $name, $force);
    }

    private function makeComponent(string $type, string $name, bool $force): void
    {
        $template = $this->getStubContents($type, $name);
        $path = $this->getPath($type, $name);

        if (!$force && file_exists($path)) {
            throw new \RuntimeException("File already exists: $path");
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_put_contents($path, $template)) {
            echo "Component successfully created: $path\n";
        } else {
            throw new \RuntimeException("Error creating component: $path");
        }
    }

    private function getStubContents(string $type, string $name): string
    {
        $stubFile = $this->getStubPath($type);

        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Template not found: $stubFile");
        }

        $template = file_get_contents($stubFile);

        return $this->replacePlaceholders($template, $name);
    }

    private function getStubPath(string $type): string
    {
        $filename = match ($type) {
            'rest-controller' => 'restController.stub',
            default => $type . '.stub'
        };

        return $this->stubPath . $filename;
    }

    private function replacePlaceholders(string $template, string $name): string
    {
        $replacements = [
            '{{name}}' => $name,
            '{{namespace}}' => $this->getNamespace($name),
            '{{class}}' => $this->getClassName($name),
            '{{tableName}}' => $this->getTableName($name),
            '{{modelName}}' => $this->getModelName($name),
            '{{date}}' => date('Y-m-d H:i:s'),
            '{{timestamp}}' => time(),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    private function getNamespace(string $name): string
    {
        // Implement namespace logic if needed
        return 'App';
    }

    private function getClassName(string $name): string
    {
        return ucfirst($name); // Keep the first letter uppercase
    }

    private function getModelName(string $name): string
    {
        return ucfirst($name); // Keep the first letter uppercase
    }

    private function getTableName(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name)) . 's';
    }

    private function getPath(string $type, string $name): string
    {
        $basePath = dirname(__DIR__, 5);

        if (!is_dir($basePath)) {
            throw new \RuntimeException("Invalid base path: $basePath");
        }

        $paths = [
            'controller' => '/app/Controllers/',
            'rest-controller' => '/app/Controllers/Api/',
            'model' => '/app/Models/',
            'middleware' => '/app/Middleware/',
            'migration' => '/database/migrations/' . date('Y_m_d_His') . '_',
            'seeder' => '/database/seeders/' // Adicionando caminho para seeders
        ];

        $suffixes = [
            'controller' => 'Controller',
            'rest-controller' => 'Controller',
            'middleware' => 'Middleware',
            'migration' => 'Migration',
            'model' => '',
            'seeder' => 'Seeder' // Adicionando sufixo para seeders
        ];

        $suffix = $suffixes[$type] ?? '';
        $name = ucfirst($name);

        return $basePath . $paths[$type] . $name . $suffix . '.php';
    }
}
