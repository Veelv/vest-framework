<?php

namespace Vest\Console;

class FrontendCommand extends Command
{
    protected $name = 'vest/frontend';
    protected $description = 'Gera configuração inicial do frontend (React/Vue)';

    private $frameworks = ['react', 'vue'];
    private $basePath;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 5);
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

        $framework = strtolower($parsed['args'][0]);
        $useTypeScript = isset($parsed['options']['typescript']);

        if (!in_array($framework, $this->frameworks)) {
            throw new \InvalidArgumentException(
                "Framework inválido. Disponíveis: " . implode(', ', $this->frameworks)
            );
        }

        $this->generateFrontend($framework, $useTypeScript);
    }

    private function generateFrontend(string $framework, bool $useTypeScript): void
    {
        $this->generateFile('package.json', $this->getPackageJson($framework, $useTypeScript));
        $this->generateFile('vite.config.' . ($useTypeScript ? 'ts' : 'js'), $this->getViteConfig($framework));
        $this->generateFile('tailwind.config.js', $this->getTailwindConfig());
        $this->generateFile('postcss.config.js', $this->getPostCssConfig());

        if ($useTypeScript) {
            $this->generateFile('tsconfig.json', $this->getTsConfig());
            $this->generateFile('env.d.ts', $this->getEnvDts());
        }

        echo "Arquivos de frontend gerados com sucesso!\n";
        echo "Execute:\n";
        echo "npm install\n";
        echo "npm run dev\n";
    }

    private function generateFile(string $filename, string $content): void
    {
        $path = $this->basePath . '/' . $filename;

        if (file_exists($path)) {
            throw new \RuntimeException("Arquivo já existe: $filename");
        }

        if (file_put_contents($path, $content)) {
            echo "Arquivo gerado: $filename\n";
        } else {
            throw new \RuntimeException("Erro ao gerar arquivo: $filename");
        }
    }

    private function getPackageJson(string $framework, bool $useTypeScript): string
    {
        $dependencies = [
            'common' => [
                'dependencies' => [
                    "axios" => "^1.7.7"
                ],
                'devDependencies' => [
                    'tailwindcss' => '^3.4.13',
                    'postcss' => '^8.4.47',
                    'autoprefixer' => '^10.4.20',
                    'vite' => '^5.4.8',                    
                    'vite-plugin-env-compatible' => '^2.0.1'
                ]
            ],
            'react' => [
                'dependencies' => [
                    'react' => '^18.3.1',
                    'react-dom' => '^18.3.1',
                    'react-router' => '^6.27.0',
                    'react-router-dom' => '^6.27.0'
                ],
                'devDependencies' => $useTypeScript ? [
                    '@types/react' => '^18.2.15',
                    '@types/react-dom' => '^18.2.7',
                    'typescript' => '^5.6.3',
                    'react-refresh' => '^0.14.2',
                    '@vitejs/plugin-react' => '4.3.3'
                ] : []
            ],
            'vue' => [
                'dependencies' => [
                    'vue' => '^3.3.4'
                ],
                'devDependencies' => $useTypeScript ? [
                    'vue-tsc' => '^1.8.5',
                    'typescript' => '^5.6.3',
                    '@vitejs/plugin-react' => '5.1.4'
                ] : []
            ]
        ];

        $packageJson = [
            'name' => 'vest/frontend',
            'version' => '1.0.0',
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
                'preview' => 'vite preview'
            ],
            'dependencies' => array_merge(
                $dependencies['common']['dependencies'],
                $dependencies[$framework]['dependencies']
            ),
            'devDependencies' => array_merge(
                $dependencies['common']['devDependencies'],
                $dependencies[$framework]['devDependencies']
            )
        ];

        return json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function getViteConfig(string $framework): string
    {
        return <<<CONFIG
            import { defineConfig } from 'vite'
            import {$framework} from '@vitejs/plugin-{$framework}'
            import tsConfigPaths from 'vite-tsconfig-paths'
            import envCompatible from 'vite-plugin-env-compatible'

            function defineVest(entries: string[]) {
                return {
                    name: "vest-plugin",
                    config: () => ({
                    build: {
                        outDir: "public/resources",
                        manifest: true,
                        rollupOptions: {
                        input: entries,
                        },
                    },
                    server: {
                        hmr: {
                        protocol: "ws",
                        host: "localhost",
                        port: 5175,
                        },
                        port: 5175,
                        cors: true,
                    },
                    }),
                };
            }

            export default defineConfig({
                envPrefix: "APP_",
                plugins: [
                    {$framework}(),
                    defineVest(["resources/css/app.css", "resources/js/App.tsx"]),
                    envCompatible(),
                    tsConfigPaths(),
                ],
                server:{
                    fs: {
                    allow: ['..'],
                    },
                    watch: {
                    usePolling: true,
                    },
                }
            })
        CONFIG;
    }

    private function getTailwindConfig(): string
    {
        return <<<CONFIG
            /** @type {import('tailwindcss').Config} */
            export default {
            content: ["./resources/**/*.{html,js,tsx,jsx}"],
            darkMode: 'class',
            theme: {
                extend: {},
            },
            plugins: [],
            }
        CONFIG;
    }

    private function getPostCssConfig(): string
    {
        return <<<CONFIG
        export default {
            plugins: {
                tailwindcss: {},
                autoprefixer: {},
            },
        }
        CONFIG;
    }

    private function getTsConfig(): string
    {
        return <<<CONFIG
        {
        "compilerOptions": {
            "target": "ES2020",
            "useDefineForClassFields": true,
            "lib": ["ES2020", "DOM", "DOM.Iterable"],
            "module": "ESNext",
            "skipLibCheck": true,
            "moduleResolution": "bundler",
            "allowImportingTsExtensions": true,
            "resolveJsonModule": true,
            "isolatedModules": true,
            "noEmit": true,
            "strict": true,
            "noUnusedLocals": true,
            "noUnusedParameters": true,
            "noFallthroughCasesInSwitch": true
        },
        "include": ["resources/js", "env.d.ts", "env.d.ts"]
        }
        CONFIG;
    }

    private function getEnvDts(): string
    {
        return "/// <reference types=\"vite/client\" />";
    }
}
