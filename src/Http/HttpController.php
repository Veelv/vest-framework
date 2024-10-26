<?php
namespace Vest\Http;

use Vest\Support\ViewFactory;

abstract class HttpController  
{ 
    protected ViewFactory $viewFactory;

    public function __construct()  
    {  
        $this->viewFactory = new ViewFactory(); // Instância o ViewFactory aqui
    }  

    /**
     * Renderiza uma view.
     *
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return Response
     */
    protected function view(string $view, array $data = [])
    {
        $content = $this->viewFactory->make($view, $data);
        return $content;
    }
}
