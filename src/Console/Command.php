<?php

namespace Vest\Console;

/**
 * Classe base para comandos de console.
 */
abstract class Command
{
    protected $signature;
    protected $description;

    /**
     * Define a assinatura do comando.
     *
     * @param string $signature
     * @return $this
     */
    public function setSignature(string $signature): self
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * Retorna a assinatura do comando.
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Define a descrição do comando.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Retorna a descrição do comando.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Executa o comando.
     *
     * @return void
     */
    abstract public function execute(): void;
}
