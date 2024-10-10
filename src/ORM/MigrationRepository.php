<?php

namespace Vest\Database;

use Vest\ORM\Connection;
use Vest\ORM\QueryBuilder;
use Vest\ORM\Schema;

class MigrationRepository  
{  
    protected Connection $connection;  
    protected string $table = 'migrations';  

    public function __construct(Connection $connection)  
    {  
        $this->connection = $connection;  
        $this->ensureMigrationTableExists();  
    }  

    protected function ensureMigrationTableExists(): void  
    {  
        $queryBuilder = new QueryBuilder($this->connection->getConnection());  
        $schema = new Schema($queryBuilder);  
        
        if (!$this->migrationTableExists($queryBuilder)) {
            $schema->create($this->table, function ($table) {  
                $table->id();  
                $table->string('migration');  
                $table->integer('batch');  
                $table->timestamp('executed_at');  
            });  
        }  
    }

    protected function migrationTableExists(QueryBuilder $queryBuilder): bool
    {
        $queryBuilder->table($this->table);  
        return !empty($queryBuilder->get());
    }

    public function getMigrations(): array  
    {  
        return $this->fetchAll("SELECT * FROM {$this->table} ORDER BY batch ASC, migration ASC");  
    }  

    public function log(string $file, int $batch): void  
    {  
        $this->execute("INSERT INTO {$this->table} (migration, batch, executed_at) VALUES (?, ?, ?)", [
            $file,
            $batch,
            date('Y-m-d H:i:s')
        ]);  
    }  

    public function getLast(): array  
    {  
        return $this->fetchAll("SELECT * FROM {$this->table} WHERE batch = (SELECT MAX(batch) FROM {$this->table})");  
    }  

    public function delete(string $migration): void  
    {  
        $this->execute("DELETE FROM {$this->table} WHERE migration = ?", [$migration]);  
    }  

    public function getMigrationById(int $id): array  
    {  
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);  
    }  

    public function getMigrationsByBatch(int $batch): array  
    {  
        return $this->fetchAll("SELECT * FROM {$this->table} WHERE batch = ?", [$batch]);  
    }  

    public function updateMigration(string $migration, array $data): void  
    {  
        $setClause = implode(', ', array_map(fn($field) => "$field = ?", array_keys($data)));
        $query = "UPDATE {$this->table} SET $setClause WHERE migration = ?";
        $params = array_values($data);
        $params[] = $migration;
        $this->execute($query, $params);  
    }  

    public function deleteBatch(int $batch): void  
    {  
        $this->execute("DELETE FROM {$this->table} WHERE batch = ?", [$batch]);  
    }  

    public function truncate(): void  
    {  
        $this->connection->getConnection()->exec("TRUNCATE TABLE {$this->table}");  
    }  

    protected function fetch(string $query, array $params = []): array  
    {  
        $stmt = $this->connection->getConnection()->prepare($query);  
        $stmt->execute($params);  
        return $stmt->fetch();  
    }

    protected function fetchAll(string $query, array $params = []): array  
    {  
        $stmt = $this->connection->getConnection()->prepare($query);  
        $stmt->execute($params);  
        return $stmt->fetchAll();  
    }

    protected function execute(string $query, array $params = []): void  
    {  
        $stmt = $this->connection->getConnection()->prepare($query);  
        $stmt->execute($params);  
    }

    public function handleError(\Exception $e): void  
    {  
        // Handle errors and exceptions that occur during migration execution
        // You can add logic to log errors to a file, for example
        $this->logError($e->getMessage());
    }  

    public function logError(string $message): void  
    {  
        // Log errors to a file
        // Implement the logging mechanism as needed
        error_log($message, 3, '/path/to/error.log'); // Adjust the path as needed
    }  
}