<?php
namespace Vest\Console;
class ConfigCommand extends Command {
    protected $name = 'config';
    protected $description = 'Gerencia configurações da aplicação';

    public function execute(array $args): void {
        $parsed = $this->parseArgs($args);
        
        if (empty($parsed['args'])) {
            $this->showHelp();
            return;
        }

        $action = $parsed['args'][0];
        $key = $parsed['args'][1] ?? null;
        $value = $parsed['args'][2] ?? null;

        switch ($action) {
            case 'set':
                if (!$key || !$value) {
                    throw new \InvalidArgumentException("Chave e valor são necessários");
                }
                $this->setConfig($key, $value);
                break;
            case 'get':
                if (!$key) {
                    throw new \InvalidArgumentException("Chave é necessária");
                }
                $this->getConfig($key);
                break;
            default:
                throw new \InvalidArgumentException("Ação inválida: $action");
        }
    }

    private function showHelp(): void {
        echo "Uso: config <ação> <chave> [valor]\n";
        echo "Ações disponíveis:\n";
        echo "  get <chave>          Obtém valor de uma configuração\n";
        echo "  set <chave> <valor>  Define valor de uma configuração\n";
    }

    private function setConfig(string $key, string $value): void {
        // Implementar lógica de configuração aqui
        echo "Configuração definida: $key = $value\n";
    }

    private function getConfig(string $key): void {
        // Implementar lógica de obtenção aqui
        echo "Valor da configuração $key\n";
    }
}