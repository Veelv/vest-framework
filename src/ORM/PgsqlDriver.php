<?php
namespace Vest\ORM;

use PDO;

class PgsqlDriver implements Driver
{
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);
        return new PDO($dsn, $config['username'], $config['password'], $this->getOptions());
    }

    public function buildDsn(array $config): string
    {
        $host = $config['host'];
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        return "pgsql:host={$host};port={$port};dbname={$database}";
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