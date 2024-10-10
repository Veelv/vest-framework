<?php

namespace Vest\Auth;

use Exception;

/**
 * Class SessionManager
 *
 * Gerencia sessões de usuário, incluindo a criação, recuperação, 
 * e destruição de dados de sessão. Implementa controle de
 * expiração de sessão e regeneração de ID de sessão para segurança.
 */
class Session
{
    protected string $sessionName;       // Nome da sessão
    protected int $expiryTime;            // Tempo de expiração da sessão em segundos
    protected array $sessionOptions;      // Opções de configuração da sessão

    /**
     * Construtor da classe SessionManager.
     *
     * @param string $sessionName Nome da sessão (padrão é 'user_session').
     * @param int $expiryTime Tempo de expiração da sessão em segundos (padrão é 3600).
     * @param array $sessionOptions Opções de configuração da sessão.
     */
    public function __construct(string $sessionName = 'user_session', int $expiryTime = 3600, array $sessionOptions = [])
    {
        $this->sessionName = $sessionName;
        $this->expiryTime = $expiryTime;
        $this->sessionOptions = array_merge([
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_secure' => false,
            'cookie_httponly' => true,
        ], $sessionOptions);

        session_name($this->sessionName); // Define o nome da sessão
        session_set_cookie_params($this->sessionOptions); // Configura os parâmetros do cookie
        session_start(); // Inicia a sessão

        // Verifica a última atividade para destruir a sessão se estiver expirada
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->expiryTime)) {
            $this->destroy(); // Destrói a sessão se expirada
        }
        $_SESSION['last_activity'] = time(); // Atualiza o timestamp da última atividade
    }

    /**
     * Armazena um valor na sessão com a chave especificada.
     *
     * @param string $key A chave sob a qual o valor será armazenado.
     * @param mixed $value O valor a ser armazenado na sessão.
     * @throws Exception Se o valor não puder ser armazenado.
     */
    public function set(string $key, $value): void
    {
        if (is_null($value)) {
            throw new Exception("Valor não pode ser nulo."); // Lança exceção se o valor for nulo
        }
        $_SESSION[$key] = $value; // Armazena o valor na sessão
    }

    /**
     * Recupera o valor armazenado na sessão para a chave especificada.
     *
     * @param string $key A chave do valor que se deseja recuperar.
     * @return mixed O valor armazenado na sessão ou null se a chave não existir.
     */
    public function get(string $key)
    {
        return $_SESSION[$key] ?? null; // Retorna o valor ou null se não existir
    }

    /**
     * Remove um valor da sessão com a chave especificada.
     *
     * @param string $key A chave do valor que se deseja remover.
     */
    public function delete(string $key): void
    {
        unset($_SESSION[$key]); // Remove o valor da sessão
    }

    /**
     * Destrói a sessão atual, limpando todos os dados da sessão.
     */
    public function destroy(): void
    {
        session_unset(); // Limpa todos os dados da sessão
        session_destroy(); // Destrói a sessão
    }

    /**
     * Verifica se o usuário está autenticado.
     *
     * @return bool Retorna true se o usuário estiver autenticado, caso contrário, false.
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']); // Verifica se o ID do usuário está definido na sessão
    }

    /**
     * Regenera o ID da sessão de forma segura para prevenir ataques de fixação de sessão.
     */
    public function regenerateId(): void
    {
        session_regenerate_id(true); // Regenera o ID da sessão de forma segura
    }

    /**
     * Armazena uma mensagem temporária na sessão (flash message).
     *
     * @param string $key A chave da mensagem.
     * @param string $message A mensagem a ser armazenada.
     */
    public function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message; // Armazena a flash message
    }

    /**
     * Recupera e remove uma flash message da sessão.
     *
     * @param string $key A chave da mensagem.
     * @return string|null A mensagem armazenada ou null se não existir.
     */
    public function pullFlash(string $key): ?string
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]); // Remove após recuperação
            return $message; // Retorna a mensagem
        }
        return null; // Retorna null se não existir
    }

    /**
     * Garante que o token da sessão seja válido.
     *
     * @param string $token O token a ser validado.
     * @return bool Retorna true se o token for válido, caso contrário, false.
     */
    public function validateToken(string $token): bool
    {
        return isset($_SESSION['session_token']) && hash_equals($_SESSION['session_token'], $token); // Compara os tokens de forma segura
    }
}