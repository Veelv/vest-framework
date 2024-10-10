<?php

namespace Vest\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTManager
{
    // Chave secreta utilizada para codificação e decodificação dos tokens JWT
    protected string $secretKey;

    // Array de claims padrão que serão incluídos em todos os tokens gerados
    protected array $defaultClaims;

    /**
     * Construtor da classe JWTManager.
     *
     * @param string $secretKey Chave secreta utilizada para codificação e decodificação
     * @param array $defaultClaims Claims padrão a serem incluídos nos tokens gerados
     */
    public function __construct(string $secretKey, array $defaultClaims = [])
    {
        $this->secretKey = $secretKey;
        $this->defaultClaims = $defaultClaims;
    }

    /**
     * Gera um token JWT com o payload fornecido e um tempo de expiração opcional.
     *
     * @param array $payload Dados a serem incluídos no token
     * @param int $expiryTime Tempo de expiração em segundos (padrão: 3600)
     * @return string Token JWT gerado
     */
    public function generateToken(array $payload, int $expiryTime = 3600): string
    {
        // Mescla os claims padrão com os fornecidos e adiciona a expiração
        $claims = array_merge($this->defaultClaims, $payload);
        $claims['exp'] = time() + $expiryTime; // Define o tempo de expiração

        // Codifica e retorna o token com a especificação do algoritmo
        return JWT::encode($claims, $this->secretKey, 'HS256'); // Agora inclui o algoritmo
    }

    /**
     * Decodifica um token JWT e retorna os dados contidos nele.
     *
     * @param string $token Token JWT a ser decodificado
     * @return array Dados contidos no token decodificado
     * @throws \RuntimeException Se o token estiver expirado ou tiver uma assinatura inválida
     */
    public function decodeToken(string $token): array
{
    try {
        // Cria uma nova chave usando a chave secreta e o algoritmo
        $key = new Key($this->secretKey, 'HS256');
        
        // Decodifica o token usando a chave criada
        $decoded = JWT::decode($token, $key);
        
        return (array) $decoded; // Convertendo para array
    } catch (ExpiredException $e) {
        throw new \RuntimeException("Token expired", 401);
    } catch (SignatureInvalidException $e) {
        throw new \RuntimeException("Invalid token signature", 401);
    } catch (\Exception $e) {
        throw new \RuntimeException("Invalid token", 401);
    }
}

    /**
     * Verifica se um token é válido, sem lançar exceções.
     *
     * @param string $token Token a ser verificado
     * @return bool Verdadeiro se o token for válido, falso caso contrário
     */
    public function verifyToken(string $token): bool
    {
        try {
            $this->decodeToken($token); // Tenta decodificar o token
            return true; // Retorna verdadeiro se não houver exceções
        } catch (\RuntimeException $e) {
            return false; // Retorna falso se uma exceção for lançada
        }
    }

    /**
     * Extrai o ID do usuário do token JWT decodificado.
     *
     * @param string $token Token JWT a ser decodificado
     * @return string|null O ID do usuário se encontrado, ou nulo se não estiver presente
     */
    public function getUserIdFromToken(string $token): ?string
    {
        $decoded = $this->decodeToken($token); // Decodifica o token
        return $decoded['sub'] ?? null; // Retorna o ID do usuário, se disponível
    }
}