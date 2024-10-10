<?php

namespace Vest\Http;

/**
 * Classe que representa uma resposta HTTP.
 *
 * Esta classe facilita a criação e o envio de respostas HTTP, 
 * incluindo a configuração de códigos de status, cabeçalhos e corpo da resposta.
 */
class Response
{
    // Código de status HTTP padrão
    protected int $statusCode = 200;
    // Array para armazenar cabeçalhos HTTP
    protected array $headers = [];
    // Corpo da resposta HTTP
    protected string $body = '';

    // Mapeamento de códigos de status HTTP para suas mensagens correspondentes
    protected array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Define o código de status da resposta HTTP.
     *
     * @param int $code Código de status HTTP.
     * @param string|null $text Texto opcional que descreve o status.
     * @return self
     */
    public function setStatusCode(int $code, string $text = null): self
    {
        $this->statusCode = $code;
        // Se um texto não for fornecido, usa o texto padrão baseado no código
        $this->body = $text ?? $this->statusTexts[$code] ?? 'Unknown status';
        return $this;
    }

    /**
     * Define um cabeçalho HTTP.
     *
     * @param string $name Nome do cabeçalho.
     * @param string $value Valor do cabeçalho.
     * @return self
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Define o corpo da resposta HTTP.
     *
     * @param string $body Corpo da resposta.
     * @return self
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Envia uma resposta JSON ao cliente.
     *
     * @param array $data Dados a serem codificados em JSON.
     * @param int $statusCode Código de status HTTP.
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($data));
        $this->send();
    }

    /**
     * Envia uma resposta XML ao cliente.
     *
     * @param array $data Dados a serem convertidos em XML.
     * @param int $statusCode Código de status HTTP.
     */
    public function xml(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/xml');
        $this->setBody($this->arrayToXml($data));
        $this->send();
    }

    /**
     * Envia um arquivo CSV como resposta.
     *
     * @param array $data Dados a serem exportados em CSV.
     * @param string $filename Nome do arquivo a ser baixado.
     * @param int $statusCode Código de status HTTP.
     */
    public function csv(array $data, string $filename = 'file.csv', int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/csv');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        // Abre a saída para escrita
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row); // Escreve cada linha do CSV
        }
        fclose($output); // Fecha a saída

        $this->send();
    }

    /**
     * Inicia o download de um arquivo.
     *
     * @param string $filePath Caminho do arquivo a ser baixado.
     * @param string|null $name Nome do arquivo para download.
     * @param bool $forceDownload Se true, força o download do arquivo.
     */
    public function download(string $filePath, string $name = null, bool $forceDownload = true): void
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404);
            $this->setBody('File not found');
            $this->send();
            return;
        }

        $name = $name ?: basename($filePath);
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', ($forceDownload ? 'attachment' : 'inline') . '; filename="' . $name . '"');
        $this->setHeader('Content-Length', filesize($filePath));

        ob_clean(); // Limpa o buffer de saída
        flush(); // Envia o buffer atual para o cliente
        readfile($filePath); // Lê o arquivo e envia para o cliente
        exit; // Finaliza a execução do script
    }

    /**
     * Habilita a compressão GZIP para a resposta.
     *
     * @return self
     */
    public function enableCompression(): self
    {
        if (ob_get_length() === 0 && extension_loaded('zlib')) {
            ob_start('ob_gzhandler'); // Inicia o buffer de saída com compressão
        }
        return $this;
    }

    /**
     * Define cabeçalhos de cache para a resposta.
     *
     * @param string $etag ETag para identificação da versão do recurso.
     * @param int $maxAge Tempo máximo em segundos que o recurso pode ser armazenado em cache.
     * @return self
     */
    public function setCache(string $etag, int $maxAge = 3600): self
    {
        $this->setHeader('ETag', $etag);
        $this->setHeader('Cache-Control', 'public, max-age=' . $maxAge);
        return $this;
    }

    /**
     * Define um cookie a ser enviado na resposta.
     *
     * @param string $name Nome do cookie.
     * @param string $value Valor do cookie.
     * @param int $expire Data de expiração do cookie.
     * @param string $path Caminho onde o cookie está disponível.
     * @param string $domain Domínio para o qual o cookie está disponível.
     * @param bool $secure Se true, o cookie só é transmitido por conexões seguras.
     * @param bool $httponly Se true, o cookie não é acessível via JavaScript.
     * @param string $samesite A política de SameSite do cookie.
     * @return self
     */
    public function setCookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = 'Lax'
    ): self {
        // Cria a string do cookie com suas propriedades
        $cookieString = "$name=$value; Expires=" . gmdate('D, d-M-Y H:i:s T', $expire) . "; Path=$path";
        if ($domain) {
            $cookieString .= "; Domain=$domain";
        }
        if ($secure) {
            $cookieString .= "; Secure";
        }
        if ($httponly) {
            $cookieString .= "; HttpOnly";
        }
        if ($samesite) {
            $cookieString .= "; SameSite=$samesite";
        }
        $this->setHeader('Set-Cookie', $cookieString);
        return $this;
    }

    /**
     * Envia a resposta HTTP ao cliente.
     *
     * Este método define o código de status, envia cabeçalhos e o corpo da resposta.
     */
    public function send(): void
    {
        http_response_code($this->statusCode); // Define o código de status HTTP
        foreach ($this->headers as $name => $value) {
            header("$name: $value"); // Envia cada cabeçalho
        }
        echo $this->body; // Envia o corpo da resposta
        if (ob_get_length() > 0) {
            ob_end_flush(); // Limpa o buffer de saída se houver conteúdo
        }
    }

    /**
     * Converte um array em formato XML.
     *
     * @param array $data Dados a serem convertidos em XML.
     * @return string Representação em string do XML.
     */
    protected function arrayToXml(array $data): string
    {
        $xmlData = new \SimpleXMLElement('<root/>'); // Cria um novo objeto SimpleXMLElement
        array_walk_recursive($data, function ($value, $key) use ($xmlData) {
            $xmlData->addChild($key, $value); // Adiciona cada chave e valor ao XML
        });
        return $xmlData->asXML(); // Retorna a representação em string do XML
    }
}