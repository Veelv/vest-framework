<?php

namespace Vest\Console;

class RouteCommand extends Command
{
    protected $name = 'route';
    protected $description = 'Gera um novo arquivo de rotas';

    private $basePath;
    private $stubPath;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 5);
        $this->stubPath = __DIR__ . '/stubs/';
    }

    public function execute(array $args): void
    {
        $parsed = $this->parseArgs($args);

        if (empty($parsed['args'])) {
            throw new \InvalidArgumentException("Nome do arquivo de rotas é obrigatório.");
        }

        $routeName = $parsed['args'][0];
        $this->generateRouteFile($routeName);
    }

    private function generateRouteFile(string $routeName): void
    {
        $stubContent = $this->getStubContents('route');
        $fileName = $this->formatFileName($routeName);
        $path = $this->getRoutesPath() . "/$fileName.php";

        if (!is_dir($this->getRoutesPath())) {
            mkdir($this->getRoutesPath(), 0755, true);
        }

        if (file_exists($path)) {
            throw new \RuntimeException("Arquivo de rotas já existe: $path");
        }

        $content = str_replace('{{routeName}}', $routeName, $stubContent);

        if (file_put_contents($path, $content)) {
            echo "Arquivo de rotas criado: $path\n";
        } else {
            throw new \RuntimeException("Erro ao criar arquivo de rotas: $path");
        }
    }

    private function getStubContents(string $type): string
    {
        $stubFile = $this->stubPath . $type . '.stub';

        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Template não encontrado: $stubFile");
        }

        return file_get_contents($stubFile);
    }

    private function getRoutesPath(): string
    {
        return $this->basePath . '/routes';
    }

    private function formatFileName(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }
}