<?php

namespace Vest\Http;

/**
 * Classe que representa uma requisição HTTP.
 */
class Request
{
    protected array $queryParams;
    protected array $postParams;
    protected array $files;
    protected array $headers;
    protected array $cookies;
    protected string $method;
    protected string $uri;
    protected string $body;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->files = $_FILES;
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->body = file_get_contents('php://input');
    }

    /**
     * Obtém o método da requisição.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Obtém a URI da requisição.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Obtém um parâmetro de consulta.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Obtém múltiplos parâmetros de consulta.
     *
     * @param array $keys
     * @return array
     */
    public function getQueryParams(array $keys): array
    {
        return array_map(fn($key) => $this->getQueryParam($key), $keys);
    }

    /**
     * Obtém um parâmetro POST.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPostParam(string $key, $default = null)
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Obtém múltiplos parâmetros POST.
     *
     * @param array $keys
     * @return array
     */
    public function getPostParams(array $keys): array
    {
        return array_map(fn($key) => $this->getPostParam($key), $keys);
    }

    /**
     * Obtém um cabeçalho da requisição.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Obtém um cookie.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Define um cookie.
     *
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return void
     */
    public function setCookie(string $key, string $value, int $expire = 0): void
    {
        setcookie($key, $value, $expire, '/');
        $this->cookies[$key] = $value; // Atualiza a lista de cookies na classe
    }

    /**
     * Verifica se um parâmetro existe em query params.
     *
     * @param string $key
     * @return bool
     */
    public function hasQueryParam(string $key): bool
    {
        return isset($this->queryParams[$key]);
    }

    /**
     * Verifica se um parâmetro existe em post params.
     *
     * @param string $key
     * @return bool
     */
    public function hasPostParam(string $key): bool
    {
        return isset($this->postParams[$key]);
    }

    /**
     * Verifica se um método está na requisição.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strcasecmp($this->method, $method) === 0;
    }

    /**
     * Verifica se a requisição é do tipo AJAX.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Obtém todos os arquivos enviados na requisição.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Obtém um arquivo específico enviado na requisição.
     *
     * @param string $key
     * @return mixed
     */
    public function getFile(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se um arquivo foi enviado.
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtém o corpo da requisição decodificado como JSON.
     *
     * @return mixed
     */
    public function getJsonBody()
    {
        return json_decode($this->body, true);
    }

    /**
     * Verifica se a requisição é um tipo HTTP específico (GET, POST, etc.)
     *
     * @param string $method
     * @return bool
     */
    public function isHttpMethod(string $method): bool
    {
        return strcasecmp($this->method, $method) === 0;
    }

    /**
     * Obtém a URL completa da requisição.
     *
     * @return string
     */
    public function getFullUrl(): string
    {
        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $this->uri;
    }

    /**
     * Normaliza um valor para evitar injeção de código.
     *
     * @param string $value
     * @return string
     */
    public function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtém o token de autenticação da requisição.
     *
     * @return string|null
     */
    public function getAuthToken(): ?string
    {
        if ($this->hasHeader('Authorization')) {
            $parts = explode(' ', $this->getHeader('Authorization'));
            if (count($parts) === 2 && strcasecmp($parts[0], 'Bearer') === 0) {
                return $parts[1];
            }
        }
        return null;
    }

    /**
     * Verifica se um cabeçalho existe na requisição.
     *
     * @param string $key
     * @return bool
     */
    public function hasHeader(string $key): bool
    {
        return isset($this->headers[$key]);
    }
}
