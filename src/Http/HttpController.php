<?php  

namespace Vest\Http;

use Vest\Http\Request;  
use Vest\Http\Response;  
use Vest\Auth\Session;  
use Vest\Support\ViewFactory;

/**
 * Interface para controladores.
 */
interface ControllerInterface  
{  
    public function handle(Request $request): Response;  
}

/**  
 * Classe base para controladores web.  
 */  
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
     * Método handle a ser implementado pelas classes filhas.
     *
     * @param Request $request
     * @return Response
     */
    abstract public function handle(Request $request): Response; 
}
