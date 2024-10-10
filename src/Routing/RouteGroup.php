<?php

namespace Vest\Routing;

class RouteGroup
{
    protected array $attributes; // Atributos do grupo de rotas, como middleware, namespace, etc.
    protected RouteRegistrar $registrar; // O registrador de rotas que manipula a adição de rotas

    /**
     * Construtor da classe RouteGroup.
     *
     * @param array $attributes Atributos do grupo de rotas.
     * @param RouteRegistrar $registrar O registrador de rotas.
     */
    public function __construct(array $attributes, RouteRegistrar $registrar)
    {
        $this->attributes = $attributes; // Inicializa os atributos do grupo
        $this->registrar = $registrar; // Inicializa o registrador de rotas
    }

    /**
     * Cria um grupo de rotas com atributos adicionais.
     *
     * @param array $attributes Atributos adicionais para o grupo.
     * @param callable $callback Função de callback que define as rotas do grupo.
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        // Mescla os atributos existentes com os novos e chama o callback para definir as rotas
        $this->registrar->group(array_merge($this->attributes, $attributes), $callback);
    }

    /**
     * Adiciona middleware ao grupo de rotas.
     *
     * @param array $middleware Array de middlewares a serem adicionados.
     * @return self Retorna a instância atual para encadeamento de métodos.
     */
    public function middleware(array $middleware): self
    {
        // Mescla os middlewares existentes com os novos
        $this->attributes['middleware'] = array_merge(
            $this->attributes['middleware'] ?? [],
            $middleware
        );
        return $this; // Retorna a instância atual
    }

    /**
     * Define o namespace para as rotas do grupo.
     *
     * @param string $namespace O namespace a ser definido.
     * @return self Retorna a instância atual para encadeamento de métodos.
     */
    public function namespace(string $namespace): self
    {
        $this->attributes['namespace'] = $namespace; // Define o namespace
        return $this; // Retorna a instância atual
    }

    /**
     * Define um prefixo para as rotas do grupo.
     *
     * @param string $prefix O prefixo a ser adicionado.
     * @return self Retorna a instância atual para encadeamento de métodos.
     */
    public function prefix(string $prefix): self
    {
        // Adiciona o prefixo ao atributo de prefixo existente
        $this->attributes['prefix'] = ($this->attributes['prefix'] ?? '') . '/' . trim($prefix, '/');
        return $this; // Retorna a instância atual
    }

    /**
     * Define um nome para as rotas do grupo.
     *
     * @param string $name O nome a ser adicionado.
     * @return self Retorna a instância atual para encadeamento de métodos.
     */
    public function name(string $name): self
    {
        // Adiciona o nome ao atributo de nome existente
        $this->attributes['name'] = ($this->attributes['name'] ?? '') . $name;
        return $this; // Retorna a instância atual
    }

    /**
     * Define um domínio para as rotas do grupo.
     *
     * @param string $domain O domínio a ser definido.
     * @return self Retorna a instância atual para encadeamento de métodos.
     */
    public function domain(string $domain): self
    {
        $this->attributes['domain'] = $domain; // Define o domínio
        return $this; // Retorna a instância atual
    }

    /**
     * Encaminha chamadas de método não reconhecidas para o registrador de rotas.
     *
     * @param string $method Nome do método chamado.
     * @param array $parameters Parâmetros do método.
     * @return mixed Retorna o resultado da chamada ao registrador de rotas.
     */
    public function __call(string $method, array $parameters)
    {
        // Encaminha a chamada para o registrador de rotas
        return $this->registrar->$method(...$parameters);
    }
}