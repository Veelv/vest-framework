<?php

namespace Vest\Console;

/**
 * Comando para gerar novos arquivos de código.
 */
class MakeCommand extends Command
{
    public function __construct()
    {
        $this->setSignature('make')
             ->setDescription('Gera novos arquivos de código.');
    }

    /**
     * Executa o comando de geração.
     *
     * @return void
     */
    public function execute(): void
    {
        // Implementar a lógica para gerar arquivos de código
        echo "Arquivos gerados com sucesso." . PHP_EOL;
    }
}
