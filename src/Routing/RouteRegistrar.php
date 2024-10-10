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
        // Adiciona os atributos do grupo à pilha
        $this->groupStack[] = $attributes;

        // Executa o callback com o registrador atual
        $callback($this);

        // Remove os atributos do grupo da pilha
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
        $methods = (array) $methods; // Garante que métodos sejam um array

        $route = null;
        // Registra a rota para cada método fornecido
        foreach ($methods as $method) {
            $route = $this->router->add($method, $this->prefix($uri), $this->action($action));
            $this->applyGroupAttributes($route); // Aplica atributos do grupo, se houver
        }

        return $route; // Retorna a rota registrada
    }

    /**
     * Aplica o prefixo de grupo ao URI.
     *
     * @param string $uri O URI original.
     * @return string O URI com o prefixo aplicado.
     */
    protected function prefix(string $uri): string
    {
        $prefix = $this->getGroupAttribute('prefix') ?? ''; // Obtém o prefixo do grupo
        return $prefix . '/' . trim($uri, '/'); // Retorna o URI com o prefixo aplicado
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