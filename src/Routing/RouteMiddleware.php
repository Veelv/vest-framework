<?php

namespace Vest\Routing;

class RouteMiddleware
{
    protected array $middlewares = []; // Array que armazena middlewares associados a rotas

    /**
     * Adiciona um middleware a uma rota específica.
     *
     * @param string $route A rota à qual o middleware será associado.
     * @param callable $middleware O middleware a ser adicionado.
     * @return void
     */
    public function add(string $route, callable $middleware): void
    {
        // Associa o middleware à rota especificada
        $this->middlewares[$route] = $middleware;
    }

    /**
     * Executa o middleware associado a uma rota específica, se existir.
     *
     * @param string $route A rota para a qual o middleware será executado.
     * @return void
     */
    public function handle(string $route): void
    {
        // Verifica se há um middleware associado à rota
        if (isset($this->middlewares[$route])) {
            // Executa o middleware associado à rota
            call_user_func($this->middlewares[$route]);
        }
    }
}