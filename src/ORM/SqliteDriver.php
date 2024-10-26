<?php
namespace Vest\ORM;

use PDO;

class SqliteDriver implements Driver
{
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);
        return new PDO($dsn, null, null, $this->getOptions());
    }

    public function buildDsn(array $config): string
    {
        $database = $config['database'];
        return "sqlite:{$database}";
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
