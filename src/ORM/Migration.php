<?php 

namespace Vest\ORM;

use Vest\ORM\Schema\Schema;
use PDO;

abstract class Migration
{
    protected static ?PDO $connection = null;

    /**
     * Define a conexão PDO para todas as migrações.
     *
     * @param PDO $connection
     */
    public static function setConnection(PDO $connection)
    {
        self::$connection = $connection;
    }

    /**
     * Execute as migrações.
     */
    abstract public function up(): void;

    /**
     * Reverta as migrações.
     */
    abstract public function down(): void;

    /**
     * Obtém a conexão PDO.
     *
     * @return PDO
     * @throws \RuntimeException se a conexão não estiver configurada
     */
    protected function getConnection(): PDO
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Conexão com o banco de dados não configurada para migrações');
        }
        return self::$connection;
    }

    /**
     * Executa uma consulta SQL bruta.
     *
     * @param string $sql
     * @return bool
     */
    protected function raw(string $sql): bool
    {
        return $this->getConnection()->exec($sql) !== false;
    }

    /**
     * Inicia uma transação.
     */
    protected function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Confirma uma transação.
     */
    protected function commit(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Reverte uma transação.
     */
    protected function rollBack(): void
    {
        $this->getConnection()->rollBack();
    }

    /**
     * Executa uma consulta SQL e retorna um PDOStatement.
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    protected function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}