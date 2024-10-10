<?php

namespace Vest\Console;

/**
 * Comando para listar todos os comandos disponíveis.
 */
class ListCommand extends Command
{
    protected $commandCollection;

    public function __construct(CommandCollection $commandCollection)
    {
        $this->commandCollection = $commandCollection;
        $this->setSignature('list')
             ->setDescription('Lista todos os comandos disponíveis.');
    }

    /**
     * Executa o comando de listagem.
     *
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->commandCollection->all() as $command) {
            echo $command->getSignature() . ': ' . $command->getDescription() . PHP_EOL;
        }
    }
}
