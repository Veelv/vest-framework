<?php
namespace Vest\Http;

use Vest\Http\Response;
use Vest\Http\Request;
use Vest\Exceptions\HttpException;

abstract class RestController
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Processa a requisição e chama o método apropriado.
     *
     * @return Response
     */
    public function handle(): Response
    {
        try {
            $method = strtolower($this->request->getMethod()); // Obtém o método HTTP da requisição

            // Verifica se o método existe
            if (!method_exists($this, $method)) {
                return $this->methodNotAllowed();
            }

            // Chama o método correspondente ao verbo HTTP
            return $this->$method();
        } catch (HttpException $e) {
            return $this->handleHttpException($e);
        } catch (\Exception $e) {
            return $this->handleGeneralException($e);
        }
    }

    protected function methodNotAllowed(): Response
    {
        return $this->response->setStatusCode(405, 'Method Not Allowed'); // Retorna 405 se o método não for permitido
    }

    protected function handleHttpException(HttpException $e): Response
    {
        return $this->response->setStatusCode($e->getCode(), $e->getMessage()); // Lida com exceções HTTP
    }

    protected function handleGeneralException(\Exception $e): Response
    {
        return $this->response->setStatusCode(500, 'Internal Server Error'); // Lida com exceções gerais
    }

    // Métodos padrão que podem ser implementados ou sobrescritos nas subclasses
    protected function index(): Response
    {
        // Implementação do método index
        return $this->respondWithData(['message' => 'Index method']);
    }

    protected function create(): Response
    {
        // Implementação do método create
        return $this->respondWithData(['message' => 'Create method']);
    }

    protected function store(): Response
    {
        // Implementação do método store
        return $this->respondWithData(['message' => 'Store method']);
    }

    protected function show($id): Response
    {
        // Implementação do método show
        return $this->respondWithData(['message' => 'Show method', 'id' => $id]);
    }

    protected function edit($id): Response
    {
        // Implementação do método edit
        return $this->respondWithData(['message' => 'Edit method', 'id' => $id]);
    }

    protected function update($id): Response
    {
        // Implementação do método update
        return $this->respondWithData(['message' => 'Update method', 'id' => $id]);
    }

    protected function destroy($id): Response
    {
        // Implementação do método destroy
        return $this->respondWithData(['message' => 'Destroy method', 'id' => $id]);
    }

    protected function respondWithData(array $data, int $statusCode = 200): Response
    {
        return $this->response->setStatusCode($statusCode)->json($data); 
    }

    protected function respondWithError(array $data, string $message = null, int $statusCode): Response
    {
        $this->response->json($data);
        $this->response->setBody(json_encode(['error' => $message])); // Define o corpo da resposta com erro
        return $this->response->setStatusCode($statusCode); // Define o código de status da resposta
    }

    /**
     * Valida os dados de entrada com as regras especificadas.
     *
     * @param array $rules Regras de validação.
     * @param array|null $messages Mensagens personalizadas (opcional).
     * @throws HttpException Se houver erros de validação.
     */
}