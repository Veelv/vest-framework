<?php

namespace Vest\Http;

use Exception;
use Vest\Auth\Session;
use Vest\Http\Request;
use Vest\Http\Response;
use Vest\Support\ViewFactory;

interface ControllerInterface  
{  
    public function __call(string $name, array $arguments); // Adiciona método mágico
}

abstract class HttpController implements ControllerInterface  
{ 
    protected Request $request;  
    protected Response $response;  
    protected ViewFactory $viewFactory;

    public function __construct(Request $request, Response $response, ViewFactory $viewFactory)  
    {  
        $this->request = $request;  
        $this->response = $response;  
        $this->viewFactory = $viewFactory;
    }  

    /**
     * Renderiza uma view.
     *
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return Response
     */
    protected function view(string $view, array $data = []): Response
    {
        $content = $this->viewFactory->make($view, $data);
        return new Response($content);
    }

    /**
     * Acesso à sessão.
     *
     * @return Session
     */
    protected function session(): Session
    {
        return new Session(); // Supondo que você tenha uma instância de sessão assim.
    }

    /**
     * Método mágico para chamar métodos não definidos.
     *
     * @param string $name Nome do método chamado.
     * @param array $arguments Argumentos passados para o método.
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        throw new Exception("Método [$name] não encontrado no controlador.");
    }
}
