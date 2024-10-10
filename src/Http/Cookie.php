<?php

namespace Vest\Http;

class Cookie
{
    protected string $name;       // Nome do cookie
    protected string $value;      // Valor do cookie
    protected int $expiryTime;    // Tempo de expiração do cookie
    protected string $path;       // Caminho onde o cookie estará disponível
    protected bool $httpOnly;     // Se o cookie é acessível apenas por HTTP
    protected bool $secure;       // Se o cookie deve ser enviado apenas por HTTPS
    protected string $domain;     // Domínio do cookie
    protected bool $sameSite;     // Política SameSite para o cookie

    /**
     * Construtor da classe CookieManager.
     *
     * @param string $name Nome do cookie.
     * @param string $value Valor do cookie.
     * @param int $expiryTime Tempo de expiração do cookie em segundos (padrão é 3600).
     * @param string $path Caminho onde o cookie estará disponível (padrão é '/').
     * @param bool $httpOnly Define se o cookie é acessível apenas por HTTP (padrão é true).
     * @param bool $secure Define se o cookie deve ser enviado apenas por HTTPS (padrão é false).
     * @param string $domain Domínio do cookie (padrão é vazio).
     * @param string $sameSite Política SameSite do cookie ('Lax', 'Strict' ou null).
     */
    public function __construct(string $name, string $value = '', int $expiryTime = 3600, string $path = '/', bool $httpOnly = true, bool $secure = false, string $domain = '', string $sameSite = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->expiryTime = $expiryTime;
        $this->path = $path;
        $this->httpOnly = $httpOnly;
        $this->secure = $secure;
        $this->domain = $domain;
        $this->sameSite = $sameSite;
    }

    /**
     * Define o cookie com as propriedades configuradas.
     *
     * @throws \RuntimeException Se ocorrer um erro ao definir o cookie.
     */
    public function set(): void
    {
        if (!setcookie(
            $this->name,
            $this->value,
            [
                'expires' => time() + $this->expiryTime,
                'path' => $this->path,
                'domain' => $this->domain,
                'secure' => $this->secure,
                'httponly' => $this->httpOnly,
                'samesite' => $this->sameSite,
            ]
        )) {
            throw new \RuntimeException("Não foi possível definir o cookie: {$this->name}");
        }
    }

    /**
     * Recupera o valor do cookie pelo nome.
     *
     * @return string|null O valor do cookie ou null se não existir.
     */
    public function get(): ?string
    {
        return $_COOKIE[$this->name] ?? null; // Retorna o valor do cookie ou null se não existir
    }

    /**
     * Exclui o cookie definindo seu tempo de expiração para o passado.
     *
     * @throws \RuntimeException Se ocorrer um erro ao excluir o cookie.
     */
    public function delete(): void
    {
        if (!setcookie($this->name, '', time() - 3600, $this->path, $this->domain)) {
            throw new \RuntimeException("Não foi possível excluir o cookie: {$this->name}");
        }
    }

    /**
     * Verifica se o cookie está presente.
     *
     * @return bool True se o cookie existir, caso contrário, false.
     */
    public function exists(): bool
    {
        return isset($_COOKIE[$this->name]);
    }

    /**
     * Atualiza o valor do cookie.
     *
     * @param string $value Novo valor do cookie.
     */
    public function update(string $value): void
    {
        $this->value = $value;
        $this->set(); // Reconfigura o cookie com o novo valor
    }
}
