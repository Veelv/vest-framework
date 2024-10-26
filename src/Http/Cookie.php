<?php

namespace Vest\Http;

/**
 * Class Cookie
 * 
 * Gerencia cookies HTTP, incluindo criação, leitura e remoção.
 */
class Cookie
{
    protected string $name;
    protected string $value;
    protected int $expiryTime;
    protected string $path;
    protected bool $httpOnly;
    protected bool $secure;
    protected string $domain;
    protected ?string $sameSite;

    /**
     * Construtor da classe Cookie.
     */
    public function __construct(
        string $name,
        string $value = '',
        int $expiryTime = 3600,
        string $path = '/',
        bool $httpOnly = true,
        bool $secure = false,
        string $domain = '',
        ?string $sameSite = 'Lax'
    ) {
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
     */
    public function set(): void
    {
        $options = [
            'expires' => time() + $this->expiryTime,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite
        ];

        if (!setcookie($this->name, $this->value, $options)) {
            throw new \RuntimeException("Não foi possível definir o cookie: {$this->name}");
        }
    }

    /**
     * Recupera o valor do cookie.
     */
    public function get(): ?string
    {
        return $_COOKIE[$this->name] ?? null;
    }

    /**
     * Exclui o cookie.
     */
    public function delete(): void
    {
        $options = [
            'expires' => time() - 3600,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite
        ];

        setcookie($this->name, '', $options);
    }

    /**
     * Verifica se o cookie existe.
     */
    public function exists(): bool
    {
        return isset($_COOKIE[$this->name]);
    }

    /**
     * Atualiza o valor do cookie.
     */
    public function update(string $value): void
    {
        $this->value = $value;
        $this->set();
    }
}