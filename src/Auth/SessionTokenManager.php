<?php

namespace Vest\Auth;

use Exception;

/**
 * Class SessionTokenManager
 *
 * Gerencia tokens de sessão, permitindo a definição, recuperação
 * e remoção de tokens associados à sessão do usuário.
 */
class SessionTokenManager
{
    protected Session $sessionManager; // Instância do SessionManager

    /**
     * Construtor da classe SessionTokenManager.
     *
     * @param Session $sessionManager Instância do SessionManager.
     */
    public function __construct(Session $sessionManager)
    {
        $this->sessionManager = $sessionManager; // Armazena a instância do SessionManager
    }

    /**
     * Define um token de sessão na sessão atual.
     *
     * @param string $token O token a ser armazenado na sessão.
     */
    public function setToken(string $token): void
    {
        // Verifica se o token não está vazio e se é uma string
        if (empty($token) || !is_string($token)) {
            throw new Exception("Token inválido."); // Lança exceção se o token for inválido
        }
        $this->sessionManager->set('session_token', $token); // Armazena o token na sessão
    }

    /**
     * Recupera o token de sessão armazenado.
     *
     * @return string|null O token de sessão armazenado ou null se não existir.
     */
    public function getToken(): ?string
    {
        return $this->sessionManager->get('session_token'); // Recupera o token da sessão
    }

    /**
     * Remove o token de sessão.
     */
    public function deleteToken(): void
    {
        $this->sessionManager->delete('session_token'); // Remove o token da sessão
    }

    /**
     * Verifica se o token de sessão existe.
     *
     * @return bool True se o token existir, caso contrário, false.
     */
    public function tokenExists(): bool
    {
        return $this->sessionManager->get('session_token') !== null; // Verifica se o token está presente
    }

    /**
     * Gera um novo token seguro para a sessão.
     *
     * @return string O novo token gerado.
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32)); // Gera um token seguro
        $this->setToken($token); // Armazena o token na sessão
        return $token; // Retorna o token gerado
    }
}