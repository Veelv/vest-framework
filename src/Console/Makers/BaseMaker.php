<?php

namespace Vest\Console\Makers;

abstract class BaseMaker implements ComponentMaker
{
    protected $stubPath;

    public function __construct()
    {
        $this->stubPath = __DIR__ . '/../stubs/';
    }

    protected function getStubContents(string $stubName, array $replacements): string
    {
        $stubFile = $this->stubPath . $stubName . '.stub';
        
        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Stub file not found: $stubFile");
        }

        $contents = file_get_contents($stubFile);

        foreach ($replacements as $search => $replace) {
            $contents = str_replace('{{' . $search . '}}', $replace, $contents);
        }

        return $contents;
    }

    protected function createFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException("Failed to create file: $path");
        }

        echo "Created: $path\n";
    }
}