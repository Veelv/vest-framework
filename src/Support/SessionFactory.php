<?php

namespace Vest\Support;

use Vest\Http\Session;
use Vest\Support\Uuid;

/**
 * Class SessionFactory
 *
 * Gerencia o estado da sessão do usuário e suas informações.
 */
class SessionFactory
{
    protected Session $session;
    protected string $sessionId;
    protected bool $isLoggedIn;
    protected ?int $userId;
    protected ?string $username;
    protected ?string $email;
    protected array $roles;
    protected ?int $step;
    protected ?string $currentPage;
    protected ?string $referrer;
    protected string $timestamp;
    protected string $sessionFilePath;

    /**
     * Construtor da classe SessionFactory.
     */
    public function __construct(string $sessionName = 'user_session', int $expiryTime = 3600, array $sessionOptions = [])
    {
        $this->session = new Session($sessionName, $expiryTime, $sessionOptions);
        
        // Verifica se já existe um sessionId na sessão PHP
        $existingSessionId = $this->session->get('session_id');
        
        if ($existingSessionId) {
            $this->sessionId = $existingSessionId;
        } else {
            // Só gera um novo ID se não existir nenhum
            $this->sessionId = Uuid::generate();
            $this->session->set('session_id', $this->sessionId);
        }

        $this->sessionFilePath = APP_PATH . '/init/sessions/' . $this->sessionId . '.json';
        $this->loadSession();
    }

    /**
     * Carrega os dados da sessão.
     */
    private function loadSession(): void
    {
        if (file_exists($this->sessionFilePath)) {
            $data = json_decode(file_get_contents($this->sessionFilePath), true);
            if ($data) {
                $this->hydrateSessionData($data);
            } else {
                $this->initializeSession(false);
            }
        } else {
            $this->initializeSession(false);
        }
    }

    /**
     * Hidrata os dados da sessão a partir de um array.
     */
    private function hydrateSessionData(array $data): void
    {
        $this->isLoggedIn = $data['isLoggedIn'] ?? false;
        $this->userId = $data['userId'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->roles = $data['roles'] ?? [];
        $this->step = $data['step'] ?? null;
        $this->currentPage = $data['currentPage'] ?? null;
        $this->referrer = $data['referrer'] ?? null;
        $this->timestamp = $data['timestamp'] ?? date('c');
    }

    /**
     * Inicializa uma nova sessão.
     */
    public function initializeSession(bool $forceNew = false): void
    {
        if ($forceNew) {
            // Gera um novo ID somente se forçado
            $this->sessionId = Uuid::generate();
            $this->session->set('session_id', $this->sessionId);
            $this->sessionFilePath = APP_PATH . '/init/sessions/' . $this->sessionId . '.json';
        }

        $this->isLoggedIn = false;
        $this->userId = null;
        $this->username = null;
        $this->email = null;
        $this->roles = [];
        $this->step = null;
        $this->currentPage = null;
        $this->referrer = null;
        $this->timestamp = date('c');
        $this->updateSession();
    }

    /**
     * Realiza o login do usuário.
     */
    public function login(int $userId, string $username, string $email, array $roles): void
    {
        $this->isLoggedIn = true;
        $this->userId = $userId;
        $this->username = $username;
        $this->email = $email;
        $this->roles = $roles;
        
        // Regenera o ID da sessão por segurança após o login
        $this->regenerateSession();
        
        $this->updateSession();
    }

    /**
     * Atualiza informações da página atual.
     */
    public function updatePage(string $currentPage, string $referrer): void
    {
        $this->currentPage = $currentPage;
        $this->referrer = $referrer;
        $this->timestamp = date('c');
        $this->updateSession();
    }

    /**
     * Regenera o ID da sessão mantendo os dados.
     */
    protected function regenerateSession(): void
    {
        $oldSessionFile = $this->sessionFilePath;
        
        // Gera novo ID e atualiza na sessão PHP
        $this->sessionId = Uuid::generate();
        $this->session->set('session_id', $this->sessionId);
        $this->session->regenerateId();
        
        // Atualiza o caminho do arquivo
        $this->sessionFilePath = APP_PATH . '/init/sessions/' . $this->sessionId . '.json';
        
        // Move o arquivo da sessão antiga para o novo ID
        if (file_exists($oldSessionFile)) {
            rename($oldSessionFile, $this->sessionFilePath);
        }
    }

    /**
     * Realiza o logout do usuário.
     */
    public function logout(): void
    {
        $this->isLoggedIn = false;
        $this->userId = null;
        $this->username = null;
        $this->email = null;
        $this->roles = [];
        
        // Limpa a sessão PHP
        $this->session->destroy();
        
        // Remove o arquivo de sessão
        $this->deleteSessionFile();
        
        // Inicializa uma nova sessão
        $this->initializeSession(true);
    }

    /**
     * Atualiza os dados da sessão.
     */
    private function updateSession(): void
    {
        $sessionData = [
            'sessionId' => $this->sessionId,
            'isLoggedIn' => $this->isLoggedIn,
            'userId' => $this->userId ?? 0,
            'username' => $this->username ?? '',
            'email' => $this->email ?? '',
            'roles' => $this->roles ?? [],
            'step' => $this->step ?? 0,
            'currentPage' => $this->currentPage ?? '',
            'referrer' => $this->referrer ?? '',
            'timestamp' => $this->timestamp,
        ];

        file_put_contents($this->sessionFilePath, json_encode($sessionData));
    }

    /**
     * Deleta o arquivo de sessão.
     */
    private function deleteSessionFile(): void
    {
        if (file_exists($this->sessionFilePath)) {
            unlink($this->sessionFilePath);
        }
    }

    /**
     * Retorna todos os dados da sessão.
     */
    public function getSessionData(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'isLoggedIn' => $this->isLoggedIn,
            'userId' => $this->userId,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles,
            'step' => $this->step,
            'currentPage' => $this->currentPage,
            'referrer' => $this->referrer,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Define o passo atual do usuário no processo.
     */
    public function setStep(int $step): void
    {
        $this->step = $step;
        $this->updateSession();
    }

    /**
     * Obtém o passo atual do usuário no processo.
     */
    public function getStep(): ?int
    {
        return $this->step;
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    public function isAuthenticated(): bool
    {
        return $this->isLoggedIn && $this->userId !== null;
    }

    /**
     * Obtém o ID do usuário atual.
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Verifica se o usuário tem uma determinada role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }
}