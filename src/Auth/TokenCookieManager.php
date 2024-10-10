<?php

namespace Vest\Auth;

use Vest\Http\Cookie;

class TokenCookieManager
{
    protected Cookie $cookieManager; // Instância do CookieManager

    /**
     * Construtor da classe TokenCookieManager.
     *
     * @param string $cookieName Nome do cookie para armazenar o token (padrão é 'auth_token').
     * @param int $expiryTime Tempo de expiração do cookie em segundos (padrão é 3600).
     * @param string $domain Domínio do cookie.
     * @param string $sameSite Política SameSite do cookie ('Lax', 'Strict' ou null).
     */
    public function __construct(string $cookieName = 'auth_token', int $expiryTime = 3600, string $domain = '', string $sameSite = null)
    {
        $this->cookieManager = new Cookie($cookieName, '', $expiryTime, '/', true, false, $domain, $sameSite);
    }

    /**
     * Define um cookie com o token especificado.
     *
     * @param string $token O token a ser armazenado no cookie.
     */
    public function setToken(string $token): void
    {
        $this->cookieManager->update($token); // Atualiza o valor do cookie com o token
        $this->cookieManager->set(); // Define o cookie
    }

    /**
     * Recupera o token armazenado no cookie.
     *
     * @return string|null O token armazenado no cookie ou null se não existir.
     */
    public function getToken(): ?string
    {
        return $this->cookieManager->get(); // Recupera o token usando o CookieManager
    }

    /**
     * Exclui o cookie do token definindo seu tempo de expiração para o passado.
     */
    public function deleteToken(): void
    {
        $this->cookieManager->delete(); // Exclui o cookie usando o CookieManager
    }

    /**
     * Verifica se o token está armazenado no cookie.
     *
     * @return bool True se o token existir, caso contrário, false.
     */
    public function tokenExists(): bool
    {
        return $this->cookieManager->exists(); // Verifica se o cookie existe
    }
}