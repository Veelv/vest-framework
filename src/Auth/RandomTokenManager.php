<?php

namespace Vest\Auth;

use RuntimeException;

class RandomTokenManager
{
    protected string $token;
    protected int $expiryTime;

    /**
     * Construtor da classe RandomTokenManager.
     *
     * @param int $expiryTime Tempo de expiração do token em segundos (padrão é 3600).
     */
    public function __construct(int $expiryTime = 3600)
    {
        $this->expiryTime = $expiryTime; // Define o tempo de expiração
    }

    /**
     * Gera um token aleatório e o armazena na sessão.
     *
     * @return string O token gerado.
     */
    public function generateToken(): string
    {
        $this->token = bin2hex(random_bytes(16)); // Gera um token aleatório
        $_SESSION['random_token'] = [
            'token' => $this->token,
            'expires_at' => time() + $this->expiryTime // Define a data de expiração
        ];
        return $this->token; // Retorna o token gerado
    }

    /**
     * Verifica se o token é válido e não expirou.
     *
     * @param string $token O token a ser verificado.
     * @return bool Verdadeiro se o token for válido e não expirou, falso caso contrário.
     */
    public function verifyToken(string $token): bool
    {
        if (!isset($_SESSION['random_token'])) {
            return false; // Retorna falso se não houver token armazenado
        }

        // Verifica se o token corresponde e se não expirou
        if ($_SESSION['random_token']['token'] === $token && time() < $_SESSION['random_token']['expires_at']) {
            return true; // Retorna verdadeiro se o token é válido
        }

        return false; // Retorna falso se o token não for válido ou expirou
    }

    /**
     * Remove o token da sessão.
     */
    public function clearToken(): void
    {
        unset($_SESSION['random_token']); // Remove o token da sessão
    }
}
