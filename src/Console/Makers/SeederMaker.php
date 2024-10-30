<?php

namespace Vest\Console\Makers;

class SeederMaker extends BaseMaker
{
    public function make(string $name, array $options): void
    {
        if (!str_ends_with($name, 'Seeder')) {
            $name = ucfirst($name) . 'Seeder';
        }

        $replacements = [
            'name' => $name,
            'namespace' => 'Database\\Seeders',
        ];

        $contents = $this->getStubContents('seeder', $replacements);
        $path = $this->getSeederPath($name);

        $this->createFile($path, $contents);
    }

    private function getSeederPath(string $name): string
    {
        return dirname(__DIR__, 6) . '/database/seeders/' . $name . '.php';
    }
}