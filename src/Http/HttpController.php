<?php

namespace Vest\Http;

use Exception;
use Vest\Auth\Session;
use Vest\Http\Request;
use Vest\Http\Response;
use Vest\Support\ViewFactory;

abstract class HttpController  
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

    protected function view(string $view, array $data = []): Response
    {
        $content = $this->viewFactory->make($view, $data);
        return new Response($content);
    }

    protected function session(): Session
    {
        return new Session(); // Supondo que você tenha uma instância de sessão assim.
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        throw new Exception("Método [$name] não encontrado no controlador.");
    }
}