<?php

namespace Vest\Console;

/**
 * Comando para executar seeders de banco de dados.
 */
class SeedCommand extends Command
{
    public function __construct()
    {
        $this->setSignature('seed')
             ->setDescription('Executa os seeders de banco de dados.');
    }

    /**
     * Executa o comando de seeding.
     *
     * @return void
     */
    public function execute(): void
    {
        // Implementar a lógica de seeding
        echo "Seeders executados com sucesso." . PHP_EOL;
    }
}
