<?php

namespace Vest\Console;

use RuntimeException;

/**
 * Comando para gerenciar migrações de banco de dados.
 */
class MigrationCommand extends Command
{
    public function __construct()
    {
        $this->setSignature('migrate')
             ->setDescription('Executa as migrações de banco de dados.');
    }

    /**
     * Executa o comando de migração.
     *
     * @return void
     */
    public function execute(): void
    {
        // Implementar a lógica de migração
        echo "Migrações executadas com sucesso." . PHP_EOL;
    }
}
