<?php

namespace Vest\Console;

use Exception;

/**
 * Classe responsável por inicializar e executar o console.
 */
class Vest
{
    protected $kernel;

    /**
     * Construtor da classe.
     *
     * @param ConsoleKernel $kernel
     */
    public function __construct(ConsoleKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Executa um comando a partir da linha de comando.
     *
     * @param array $argv
     * @return int
     */
    public function run(array $argv): int
    {
        try {
            $command = $this->kernel->handle($argv);
            $command->execute();
            return 0;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
            return 1;
        }
    }
}
