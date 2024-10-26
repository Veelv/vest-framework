<?php
namespace Vest\ORM;

use PDO;

class SqlsrvDriver implements Driver
{
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);
        return new PDO($dsn, $config['username'], $config['password'], $this->getOptions());
    }

    public function buildDsn(array $config): string
    {
        $host = $config['host'];
        $port = $config['port'] ?? 1433;
        $database = $config['database'];
        return "sqlsrv:Server={$host},{$port};Database={$database}";
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