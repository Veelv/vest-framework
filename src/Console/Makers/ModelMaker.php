<?php

namespace Vest\Console\Makers;

class ModelMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        $name = ucfirst($name);

        $replacements = [
            'name' => $name,
            'namespace' => 'App\\Models',
            'table' => $this->getTableName($name),
        ];

        $contents = $this->getStubContents('model', $replacements);
        $path = $this->getModelPath($name);

        $this->createFile($path, $contents);
    }

    private function getTableName(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name)) . 's';
    }

    private function getModelPath(string $name): string
    {
        return dirname(__DIR__, 6) . '/app/Models/' . $name . '.php';
    }
}