<?php

namespace Vest\Console\Makers;

class RoutesMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        $isApi = in_array('--api', $options);
        $stubName = $isApi ? 'api-routes' : 'routes';
        
        $replacements = [
            'name' => strtolower($name),
        ];

        $contents = $this->getStubContents($stubName, $replacements);
        $path = $this->getRoutesPath($name, $isApi);

        $this->createFile($path, $contents);
    }

    private function getRoutesPath(string $name, bool $isApi): string
    {
        $basePath = dirname(__DIR__, 6) . '/routes';
        $fileName = strtolower($name) . ($isApi ? '_api' : '') . '.php';
        return $basePath . '/' . $fileName;
    }
}