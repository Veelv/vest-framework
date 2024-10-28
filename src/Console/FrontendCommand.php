<?php

namespace Vest\Console;

class FrontendCommand extends Command
{
    protected $name = 'vest/frontend';
    protected $description = 'Gera configuração inicial do frontend (React/Vue)';

    private $frameworks = ['react', 'vue'];
    private $basePath;
    private $stubPath;
    private $framework;
    private $useTypeScript;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 5);
        $this->stubPath = __DIR__ . '/stubs/frontend/';
    }

    public function execute(array $args): void
    {
        $parsed = $this->parseArgs($args);

        if (empty($parsed['args'])) {
            throw new \InvalidArgumentException(
                "Uso: frontend <framework> [--typescript]\n" .
                    "Frameworks disponíveis: " . implode(', ', $this->frameworks)
            );
        }

        $this->framework = strtolower($parsed['args'][0]);
        $this->useTypeScript = isset($parsed['options']['typescript']);

        if (!in_array($this->framework, $this->frameworks)) {
            throw new \InvalidArgumentException(
                "Framework inválido. Disponíveis: " . implode(', ', $this->frameworks)
            );
        }

        $this->generateFrontend();
    }

    private function generateFrontend(): void
    {
        $this->generateFromStub('package.json');
        $this->generateFromStub('vite.config.' . ($this->useTypeScript ? 'ts' : 'js'));
        $this->generateFromStub('tailwind.config.js');
        $this->generateFromStub('postcss.config.js');
        
        if ($this->useTypeScript) {
            $this->generateFromStub('tsconfig.json');
            $this->generateFromStub('env.d.ts');
        }

        $this->generateFromStub('app/Controllers/FrontendController.php');
        $this->generateFromStub('resources/views/frontend.php');

        $this->generateFrameworkFiles();

        echo "Arquivos de frontend gerados com sucesso!\n";
        echo "Execute:\n";
        echo "npm install\n";
        echo "npm run dev\n";
    }

    private function generateFrameworkFiles(): void
    {
        $extension = $this->useTypeScript ? 'tsx' : 'jsx';
        
        if ($this->framework === 'react') {
            $this->generateReactFiles($extension);
        } else {
            $this->generateVueFiles();
        }
    }

    private function generateReactFiles(string $extension): void
    {
        $files = [
            "resources/js/App.$extension",
            "resources/js/router.$extension",
            "resources/js/pages/HomePage.$extension",
            "resources/js/pages/NotFound.$extension",
            "resources/css/app.css",
        ];

        foreach ($files as $file) {
            $this->generateFromStub("react/$file");
        }
    }

    private function generateVueFiles(): void
    {
        $extension = $this->useTypeScript ? 'vue' : 'vue'; // Vue usa .vue para ambos
        $routerExtension = $this->useTypeScript ? 'ts' : 'js';

        $files = [
            "resources/js/App.$extension",
            "resources/js/router.$routerExtension",
            "resources/js/pages/HomePage.$extension",
            "resources/js/pages/NotFound.$extension",
            "resources/css/app.css",
        ];

        foreach ($files as $file) {
            $this->generateFromStub("vue/$file");
        }
    }

    private function generateFromStub(string $filename, array $additionalReplacements = []): void
    {
        $stubContent = file_get_contents($this->stubPath . $filename . '.stub');
        
        $replacements = array_merge([
            '{{framework}}' => $this->framework,
            '{{frameworkVersion}}' => $this->getFrameworkVersion(),
            '{{pluginVersion}}' => $this->getPluginVersion(),
            '{{jsExtension}}' => $this->useTypeScript ? 'ts' : 'js',
            '{{#if useReact}}' => $this->framework === 'react' ? '' : '// ',
            '{{#if useVue}}' => $this->framework === 'vue' ? '' : '// ',
            '{{#if useTypeScript}}' => $this->useTypeScript ? '' : '// ',
        ], $additionalReplacements);

        foreach ($replacements as $search => $replace) {
            $stubContent = str_replace($search, $replace, $stubContent);
        }

        $outputPath = $this->basePath . '/' . $filename;
        
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        if (file_put_contents($outputPath, $stubContent)) {
            echo "Arquivo gerado: $filename\n";
        } else {
            throw new \RuntimeException("Erro ao gerar arquivo: $filename");
        }
    }

    private function getFrameworkVersion(): string
    {
        return $this->framework === 'react' ? '18.3.1' : '3.3.4';
    }

    private function getPluginVersion(): string
    {
        return $this->framework === 'react' ? '4.3.3' : '5.1.4';
    }
}