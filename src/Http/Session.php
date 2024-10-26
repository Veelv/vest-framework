<?php

namespace Vest\Http;

use Exception;

/**
 * Class Session
 *
 * Gerencia sessões de usuário, incluindo a criação, recuperação, 
 * e destruição de dados de sessão.
 */
class Session
{
    protected string $sessionName;
    protected int $expiryTime;
    protected array $sessionOptions;
    protected Cookie $sessionCookie;

    /**
     * Construtor da classe Session.
     *
     * @param string $sessionName Nome da sessão (padrão é 'user_session').
     * @param int $expiryTime Tempo de expiração da sessão em segundos (padrão é 3600).
     * @param array $sessionOptions Opções de configuração da sessão.
     */
    public function __construct(string $sessionName = 'user_session', int $expiryTime = 3600, array $sessionOptions = [])
{
    $this->sessionName = $sessionName;
    $this->expiryTime = $expiryTime;

    // Define as opções padrão do cookie
    $defaultOptions = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    // Mescla as opções padrão com as opções fornecidas
    $this->sessionOptions = array_merge($defaultOptions, $sessionOptions);

    // Define o nome da sessão
    session_name($this->sessionName);

    // Configura os parâmetros do cookie da sessão
    session_set_cookie_params(
        $this->sessionOptions['lifetime'],
        $this->sessionOptions['path'],
        $this->sessionOptions['domain'],
        $this->sessionOptions['secure'],
        $this->sessionOptions['httponly']
    );

    // Inicia a sessão se ainda não estiver iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inicializa o cookie de sessão
    $this->sessionCookie = new Cookie('session_cookie', session_id(), $this->expiryTime);
    $this->sessionCookie->set();

    // Verifica a expiração da sessão
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->expiryTime)) {
        $this->destroy();
    }
    $_SESSION['last_activity'] = time();
}

    /**
     * Armazena um valor na sessão com a chave especificada.
     */
    public function set(string $key, $value): void
    {
        if (is_null($value)) {
            throw new Exception("Valor não pode ser nulo.");
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Recupera o valor armazenado na sessão para a chave especificada.
     */
    public function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Remove um valor da sessão com a chave especificada.
     */
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destrói a sessão atual.
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
        $this->sessionCookie->delete();
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Regenera o ID da sessão.
     */
    public function regenerateId(): void
    {
        session_regenerate_id(true);
        $this->sessionCookie->update(session_id());
    }

    /**
     * Armazena uma mensagem flash.
     */
    public function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Recupera uma mensagem flash.
     */
    public function getFlash(string $key): ?string
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
}