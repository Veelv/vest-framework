<?php

namespace Vest\ORM;

use PDO;

class Connection
{
    private Driver $driver;
    private array $config;
    private ?PDO $pdo = null;

    public function __construct(array $config, Driver $driver)
    {
        $this->config = $config;
        $this->driver = $driver;
        $this->pdo = $this->createPdo();
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    private function connect(): void
    {
        $this->pdo = $this->driver->connect($this->config);
    }

    public function getDriver()
    {
        return $this->driver;
    }

    private function createPdo()
    {
        return $this->driver->connect($this->config);
    }
}