<?php

namespace Vest\Console\Makers;

class ControllerMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        $isApi = in_array('--api', $options);
        $stubName = $isApi ? 'api-controller' : 'controller';
        
        // Se não terminar com "Controller", adiciona
        if (!str_ends_with($name, 'Controller')) {
            // Capitaliza a primeira letra antes de adicionar "Controller"
            $name = ucfirst($name) . 'Controller';
        }

        $replacements = [
            'name' => $name,
            'namespace' => 'App\\Controllers' . ($isApi ? '\\Api' : ''),
        ];

        $contents = $this->getStubContents($stubName, $replacements);
        $path = $this->getControllerPath($name, $isApi);

        $this->createFile($path, $contents);
    }

    private function getControllerPath(string $name, bool $isApi): string
    {
        $basePath = dirname(__DIR__, 6) . '/app/Controllers';
        if ($isApi) {
            $basePath .= '/Api';
        }
        // Não adiciona "Controller" aqui pois já está no nome
        return $basePath . '/' . $name . '.php';
    }
}