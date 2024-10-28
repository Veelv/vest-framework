<?php

namespace Vest\Console;

use PDO;
use Vest\ORM\QueryBuilder;

class SeedCommand extends Command
{
    protected $name = 'db:seed';
    protected $description = 'Executa o seeding do banco de dados';

    private static PDO $connection;

    public static function setConnection(PDO $connection)
    {
        self::$connection = $connection;
    }

    public function execute(array $args): void
    {
        $parsed = $this->parseArgs($args);
        
        $class = $parsed['args'][0] ?? 'DatabaseSeeder';
        $class = "Database\\Seeders\\{$class}";

        if (!class_exists($class)) {
            throw new \RuntimeException("Seeder não encontrado: $class");
        }

        $this->info("Iniciando seeding...");

        try {
            $queryBuilder = new QueryBuilder($this->connection);
            $seeder = new $class($queryBuilder);
            $seeder->run();

            $this->success("Seeding concluído com sucesso!");
        } catch (\Exception $e) {
            $this->error("Erro durante o seeding: " . $e->getMessage());
        }
    }

    protected function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m\n";
    }

    protected function success(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }
}