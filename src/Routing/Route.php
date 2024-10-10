<?php

namespace Vest\Routing;

class Route
{
    protected string $method; // Método HTTP da rota (GET, POST, etc.)
    protected string $uri; // URI da rota
    protected $action; // Ação a ser executada quando a rota for correspondida
    protected array $middlewares = []; // Array de middlewares associados à rota
    protected ?string $name = null; // Nome da rota (opcional)
    protected array $parameters = []; // Parâmetros extraídos da URI
    protected ?string $domain = null; // Domínio opcional para a rota
    protected array $middlewareExceptions = []; // Exceções de middlewares para esta rota
    protected array $where = []; // Restrições de parâmetros da rota

    /**
     * Construtor da classe Route.
     *
     * @param string $method Método HTTP da rota.
     * @param string $uri URI da rota.
     * @param mixed $action Ação a ser executada (pode ser um closure, string, etc.).
     */
    public function __construct(string $method, string $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    /**
     * Define o nome da rota.
     *
     * @param string $name Nome da rota.
     * @return self Retorna a instância atual para encadeamento.
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Obtém o nome da rota.
     *
     * @return string|null Nome da rota ou null se não definido.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Define o domínio da rota.
     *
     * @param string $domain Domínio para a rota.
     * @return self Retorna a instância atual para encadeamento.
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Obtém o domínio da rota.
     *
     * @return string|null Domínio da rota ou null se não definido.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Adiciona middlewares à rota.
     *
     * @param mixed $middleware Middleware(s) a serem adicionados.
     * @return self Retorna a instância atual para encadeamento.
     */
    public function middleware($middleware): self
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middleware);
        return $this;
    }

    /**
     * Obtém os middlewares associados à rota.
     *
     * @return array Array de middlewares.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Obtém as exceções de middlewares da rota.
     *
     * @return array Array de exceções de middlewares.
     */
    public function getMiddlewareExceptions(): array
    {
        return $this->middlewareExceptions;
    }

    /**
     * Verifica se a rota corresponde ao método e URI fornecidos.
     *
     * @param string $method Método HTTP a ser verificado.
     * @param string $uri URI a ser verificada.
     * @param string|null $domain Domínio a ser verificado (opcional).
     * @return bool Retorna true se a rota corresponder, caso contrário, false.
     */
    public function matches(string $method, string $uri, ?string $domain): bool
    {
        // Verifica se o método corresponde
        if ($this->method !== $method) {
            return false;
        }

        // Verifica se o domínio corresponde, se definido
        if ($this->domain && $this->domain !== $domain) {
            return false;
        }

        // Obtém o padrão da rota compilado e verifica se a URI corresponde
        $pattern = $this->getCompiledPattern();
        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }

        // Extrai parâmetros da URI correspondente
        $this->parameters = $this->extractParameters($matches);
        return true;
    }

    /**
     * Verifica se a URI corresponde ao padrão da rota.
     *
     * @param string $uri URI a ser verificada.
     * @return bool Retorna true se a URI corresponder ao padrão, caso contrário, false.
     */
    public function matchesUri(string $uri): bool
    {
        $pattern = $this->getCompiledPattern();
        return (bool) preg_match($pattern, $uri);
    }

    /**
     * Define restrições para os parâmetros da rota.
     *
     * @param string|array $name Nome do parâmetro ou array de restrições.
     * @param string|null $pattern Padrão opcional para o parâmetro.
     * @return self Retorna a instância atual para encadeamento.
     */
    public function where($name, $pattern = null): self
    {
        $this->where = is_array($name) ? $name : [$name => $pattern];
        return $this;
    }

    /**
     * Compila o padrão da rota para correspondência.
     *
     * @return string Padrão da rota compilado.
     */
    protected function getCompiledPattern(): string
    {
        // Substitui parâmetros na URI pelo padrão correspondente
        $pattern = preg_replace('/\{([^\}]+)\}/', '(?P<$1>[^/]+)', $this->uri);
        foreach ($this->where as $name => $constraint) {
            $pattern = str_replace("(?P<{$name}>[^/]+)", "(?P<{$name}>{$constraint})", $pattern);
        }
        return '#^' . $pattern . '$#'; // Retorna o padrão de expressão regular
    }

    /**
     * Extrai parâmetros da URI correspondente.
     *
     * @param array $matches Resultados da correspondência da expressão regular.
     * @return array Parâmetros extraídos.
     */
    protected function extractParameters(array $matches): array
    {
        // Filtra os resultados para obter apenas os parâmetros nomeados
        return array_filter($matches, function ($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Obtém os parâmetros extraídos da URI.
     *
     * @return array Parâmetros extraídos.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Obtém a ação associada à rota.
     *
     * @return mixed Ação da rota.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Obtém a URI da rota.
     *
     * @return string URI da rota.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Obtém o método da rota.
     *
     * @return string Método da rota.
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}