<?php

namespace Vest\Console;

/**
 * Comando para gerenciar configurações.
 */
class ConfigCommand extends Command
{
    public function __construct()
    {
        $this->setSignature('config')
             ->setDescription('Gerencia as configurações do framework.');
    }

    /**
     * Executa o comando de configuração.
     *
     * @return void
     */
    public function execute(): void
    {
        // Implementar a lógica de configuração
        echo "Configurações gerenciadas com sucesso." . PHP_EOL;
    }
}
