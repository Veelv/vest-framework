<?php

namespace Vest\Exceptions;

use Exception;

/**
 * Classe que representa uma exceção quando uma rota não é encontrada.
 */
class RouteNotFoundException extends Exception
{
    /**
     * Construtor para a exceção de rota não encontrada.
     *
     * @param string $message Mensagem de erro (padrão: "Route not found").
     * @param int $code Código de erro HTTP (padrão: 404).
     * @param Exception|null $previous Exceção anterior para encadeamento (opcional).
     */
    public function __construct(string $message = "Route not found", int $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous); // Chama o construtor da classe pai
    }
}

/**
 * Classe que representa uma exceção quando o método HTTP não é permitido.
 */
class MethodNotAllowedException extends Exception
{
    protected array $allowedMethods; // Métodos permitidos para a rota

    /**
     * Construtor para a exceção de método não permitido.
     *
     * @param array $allowedMethods Métodos permitidos para a rota.
     * @param string $message Mensagem de erro (padrão: "Method not allowed").
     * @param int $code Código de erro HTTP (padrão: 405).
     * @param Exception|null $previous Exceção anterior para encadeamento (opcional).
     */
    public function __construct(array $allowedMethods, string $message = "Method not allowed", int $code = 405, Exception $previous = null)
    {
        $this->allowedMethods = $allowedMethods; // Armazena os métodos permitidos
        parent::__construct($message, $code, $previous); // Chama o construtor da classe pai
    }

    /**
     * Retorna os métodos HTTP permitidos para a rota.
     *
     * @return array Lista de métodos permitidos.
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods; // Retorna os métodos permitidos
    }
}

/**
 * Classe que representa uma exceção para ações de rota inválidas.
 */
class InvalidRouteActionException extends Exception
{
    /**
     * Construtor para a exceção de ação de rota inválida.
     *
     * @param string $message Mensagem de erro (padrão: "Invalid route action").
     * @param int $code Código de erro HTTP (padrão: 500).
     * @param Exception|null $previous Exceção anterior para encadeamento (opcional).
     */
    public function __construct(string $message = "Invalid route action", int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous); // Chama o construtor da classe pai
    }
}
