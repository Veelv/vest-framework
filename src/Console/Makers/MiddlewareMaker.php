<?php

namespace Vest\Console\Makers;

class MiddlewareMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        if (!str_ends_with($name, 'Middleware')) {
            $name = ucfirst($name) . 'Middleware';
        }

        $replacements = [
            'name' => $name,
            'namespace' => 'App\\Middleware',
        ];

        $contents = $this->getStubContents('middleware', $replacements);
        $path = $this->getMiddlewarePath($name);

        $this->createFile($path, $contents);
    }

    private function getMiddlewarePath(string $name): string
    {
        return dirname(__DIR__, 6) . '/app/Middleware/' . $name . '.php';
    }
}