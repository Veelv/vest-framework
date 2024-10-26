<?php
namespace Vest\ORM;

use PDO;

class MysqlDriver implements Driver
{
    public function connect(array $config): PDO
    {
        if (!isset($config['host']) || !isset($config['database']) || !isset($config['username']) || !isset($config['password'])) {
            throw new \InvalidArgumentException('Configurações de conexão inválidas');
        }

        $dsn = $this->buildDsn($config);
        $username = $config['username'];
        $password = $config['password'];

        return new PDO($dsn, $username, $password);
    }

    public function buildDsn(array $config): string
    {
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        return "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }

    private function getOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}