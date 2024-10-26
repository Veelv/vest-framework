<?php

namespace Vest\ORM;

class Database
{
    private $driver;
    private $host;
    private $database;
    private $username;
    private $password;

    public function __construct(array $config)
    {
        $this->driver = $config['driver'];
        $this->host = $config['host'];
        $this->database = $config['database'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}