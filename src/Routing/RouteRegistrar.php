<?php

namespace Vest\Routing;

class RouteRegistrar
{
    protected Router $router; // Instância do roteador
    protected array $groupStack = []; // Pilha para armazenar atributos de grupos de rotas

    /**
     * Construtor da classe RouteRegistrar.
     *
     * @param Router $router Instância do roteador a ser utilizado.
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Registra uma rota do tipo GET.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function get(string $uri, $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Registra uma rota do tipo POST.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function post(string $uri, $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Registra uma rota do tipo PUT.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function put(string $uri, $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Registra uma rota do tipo DELETE.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function delete(string $uri, $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Registra uma rota do tipo PATCH.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function patch(string $uri, $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Registra uma rota do tipo OPTIONS.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function options(string $uri, $action): Route
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Registra uma rota que aceita qualquer método HTTP.
     *
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $uri, $action);
    }

    /**
     * Registra um recurso com rotas padrão para um controlador.
     *
     * @param string $name Nome do recurso.
     * @param string $controller Nome do controlador associado ao recurso.
     * @return void
     */
    public function resource(string $name, string $controller): void
    {
        $this->get("$name", [$controller, 'index']);
        $this->get("$name/create", [$controller, 'create']);
        $this->post("$name", [$controller, 'store']);
        $this->get("$name/{id}", [$controller, 'show']);
        $this->get("$name/{id}/edit", [$controller, 'edit']);
        $this->put("$name/{id}", [$controller, 'update']);
        $this->delete("$name/{id}", [$controller, 'destroy']);
    }

    /**
     * Cria um grupo de rotas com atributos compartilhados.
     *
     * @param array $attributes Atributos a serem aplicados ao grupo de rotas.
     * @param callable $callback Função de callback que define as rotas no grupo.
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    /**
     * Adiciona uma rota ao roteador.
     *
     * @param mixed $methods Métodos HTTP para os quais a rota será registrada.
     * @param string $uri O URI da rota.
     * @param mixed $action A ação a ser executada quando a rota é acessada.
     * @return Route A rota registrada.
     */
    protected function addRoute($methods, string $uri, $action): Route
    {
        $methods = (array) $methods;

        // Aplicar o prefixo do grupo à URI
        $uri = $this->getGroupPrefix() . '/' . trim($uri, '/');
        $uri = '/' . trim($uri, '/'); // Normalizar URI

        $route = null;
        foreach ($methods as $method) {
            $route = $this->router->add($method, $uri, $action);
            $this->applyGroupAttributes($route);
        }

        return $route;
    }

    protected function getGroupPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return trim($prefix, '/');
    }

    /**
     * Aplica o prefixo de grupo ao URI.
     *
     * @param string $uri O URI original.
     * @return string O URI com o prefixo aplicado.
     */
    protected function prefix(string $uri): string
    {
        // Remove barras duplicadas e retorna o URI com o prefixo
        return trim($uri, '/');
    }

    /**
     * Determina a ação a ser executada para a rota.
     *
     * @param mixed $action Ação fornecida.
     * @return mixed A ação com namespace aplicado, se necessário.
     */
    protected function action($action)
    {
        // Se a ação for uma string, aplica o namespace do grupo, se existir
        if (is_string($action)) {
            return $this->getGroupAttribute('namespace') ? ($this->getGroupAttribute('namespace') . '\\' . $action) : $action;
        }

        return $action; // Retorna a ação como está, se não for string
    }

    /**
     * Aplica os atributos do grupo à rota registrada.
     *
     * @param Route $route A rota à qual os atributos serão aplicados.
     * @return void
     */
    protected function applyGroupAttributes(Route $route): void
    {
        // Aplica middleware, domínio e nome, se estiverem definidos no grupo
        if ($middleware = $this->getGroupAttribute('middleware')) {
            $route->middleware($middleware);
        }

        if ($domain = $this->getGroupAttribute('domain')) {
            $route->domain($domain);
        }

        if ($name = $this->getGroupAttribute('name')) {
            $route->name($name);
        }
    }

    /**
     * Obtém um atributo do grupo atual.
     *
     * @param string $key A chave do atributo a ser obtido.
     * @return mixed O valor do atributo ou null se não existir.
     */
    protected function getGroupAttribute(string $key)
    {
        if (empty($this->groupStack)) {
            return null; // Retorna null se não houver grupos na pilha
        }

        return end($this->groupStack)[$key] ?? null; // Retorna o valor do atributo, se existir
    }
}
