<?php

namespace Vest\Routing;

class RouteCollection
{
    protected array $routes = []; // Array que contém todas as rotas
    protected array $namedRoutes = []; // Array que armazena rotas nomeadas

    /**
     * Adiciona uma rota à coleção.
     *
     * @param Route $route A rota a ser adicionada.
     * @return void
     */
    public function addRoute(Route $route): void
    {
        $this->routes[] = $route; // Adiciona a rota ao array de rotas
        // Verifica se a rota tem um nome e a adiciona ao array de rotas nomeadas
        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Tenta encontrar uma rota correspondente ao método e URI fornecidos.
     *
     * @param string $method Método HTTP a ser verificado (ex: GET, POST).
     * @param string $uri URI a ser verificada.
     * @param string|null $domain Domínio a ser verificado (opcional).
     * @return Route|null Retorna a rota correspondente ou null se não encontrada.
     */
    public function match(string $method, string $uri, ?string $domain = null): ?Route
    {
        foreach ($this->routes as $route) {
            // Verifica se a rota corresponde ao método e URI
            if ($route->matches($method, $uri, $domain)) {
                return $route; // Retorna a rota correspondente
            }
        }
        return null; // Retorna null se nenhuma rota correspondente for encontrada
    }

    /**
     * Obtém todas as rotas da coleção.
     *
     * @return array Array de rotas.
     */
    public function getRoutes(): array
    {
        return $this->routes; // Retorna todas as rotas
    }

    /**
     * Obtém uma rota pelo seu nome.
     *
     * @param string $name Nome da rota.
     * @return Route|null Retorna a rota correspondente ou null se não encontrada.
     */
    public function getRouteByName(string $name): ?Route
    {
        // Retorna a rota nomeada se existir, caso contrário, null
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Obtém os métodos HTTP permitidos para uma URI específica.
     *
     * @param string $uri URI a ser verificada.
     * @return array Array de métodos permitidos.
     */
    public function getAllowedMethods(string $uri): array
    {
        $allowedMethods = []; // Inicializa array de métodos permitidos
        foreach ($this->routes as $route) {
            // Verifica se a rota corresponde à URI
            if ($route->matchesUri($uri)) {
                $allowedMethods[] = $route->getMethod(); // Adiciona método da rota ao array
            }
        }
        return array_unique($allowedMethods); // Retorna métodos únicos
    }
}