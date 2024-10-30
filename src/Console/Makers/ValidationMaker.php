<?php

namespace Vest\Console\Makers;

class ValidationMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        if (!str_ends_with($name, 'Validation')) {
            $name = ucfirst($name) . 'Validation';
        }

        $replacements = [
            'name' => $name,
            'namespace' => 'App\\Validation',
        ];

        $contents = $this->getStubContents('validation', $replacements);
        $path = $this->getValidationPath($name);

        $this->createFile($path, $contents);
    }

    private function getValidationPath(string $name): string
    {
        return dirname(__DIR__, 6) . '/app/Validation/' . $name . '.php';
    }
}