<?php  

namespace Vest\Http;

use Vest\Http\Request;  
use Vest\Http\Response;  
use Vest\Auth\Session;  
use Vest\Http\Cookie;

interface ControllerInterface  
{  
   public function handle(Request $request): Response;  
}  

/**  
 * Classe base para controladores web.  
 */  
abstract class HttpController  
{ 
    protected Request $request;  
    protected Response $response;  
   
    public function __construct(Request $request, Response $response)  
    {  
       $this->request = $request;  
       $this->response = $response;  
    }  
   
    abstract public function handle(Request $request): Response; 
}