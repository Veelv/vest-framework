<?php

namespace Vest\Http;

use Vest\Exceptions\HttpException;

abstract class RestController
{
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request; // Armazena a instância de Request
        $this->response = $response; // Armazena a instância de Response
        $this->response->setHeader('Content-Type', 'application/json'); // Define o tipo de conteúdo padrão como JSON
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
    protected function get(): Response
    {
        return $this->methodNotAllowed(); // Retorna 405 se o método GET não for implementado
    }

    protected function post(): Response
    {
        return $this->methodNotAllowed(); // Retorna 405 se o método POST não for implementado
    }

    protected function put(): Response
    {
        return $this->methodNotAllowed(); // Retorna 405 se o método PUT não for implementado
    }

    protected function delete(): Response
    {
        return $this->methodNotAllowed(); // Retorna 405 se o método DELETE não for implementado
    }

    protected function validate(array $data, array $rules): void
    {
        // Implementar lógica de validação aqui
    }

    protected function respondWithData(array $data, int $statusCode = 200): Response
    {
        $this->response->setBody(json_encode($data)); // Define o corpo da resposta como JSON
        return $this->response->setStatusCode($statusCode); // Define o código de status da resposta
    }

    protected function respondWithError(string $message, int $statusCode): Response
    {
        $this->response->setBody(json_encode(['error' => $message])); // Define o corpo da resposta com erro
        return $this->response->setStatusCode($statusCode); // Define o código de status da resposta
    }
}
