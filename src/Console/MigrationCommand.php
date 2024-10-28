<?php

namespace Vest\Console;
use PDO;

class MigrationCommand extends Command
{
    protected $name = 'migration';
    protected $description = 'Gerencia migrações do banco de dados';
    protected $stubPath;
    private static PDO $connection;

    
    public function __construct()
    {
        $this->stubPath = __DIR__ . '/stubs/';
    }
    public static function setConnection(PDO $connection)
    {
        self::$connection = $connection;
    }

    public function execute(array $args): void
    {
        if (empty($args)) {
            $this->showHelp();
            return;
        }

        $action = $args[0];

        switch ($action) {
            case 'create':
                if (empty($args[1])) {
                    throw new \InvalidArgumentException("Nome da migração é obrigatório");
                }
                $this->create($args[1]);
                break;

            case 'run':
                $this->run();
                break;

            case 'rollback':
                $steps = isset($args[1]) ? (int)$args[1] : 1;
                $this->rollback($steps);
                break;

            case 'reset':
                $this->reset();
                break;

            case 'refresh':
                $this->refresh();
                break;

            case 'status':
                $this->status();
                break;

            default:
                throw new \InvalidArgumentException("Ação inválida: $action");
        }
    }

    private function create(string $name): void
    {
        $timestamp = date('Y_m_d_His');
        $className = $this->formatClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $path = $this->getMigrationsPath() . "/$filename";

        // Usar o stub de migração existente
        $stubContent = $this->getStubContents('migration', $name);

        if (!is_dir($this->getMigrationsPath())) {
            mkdir($this->getMigrationsPath(), 0755, true);
        }

        if (file_put_contents($path, $stubContent)) {
            $this->success("Migração criada: $filename");
        } else {
            throw new \RuntimeException("Erro ao criar migração");
        }
    }

    private function getStubContents(string $type, string $name): string
    {
        $stubFile = $this->getStubPath($type);

        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Template não encontrado: $stubFile");
        }

        $template = file_get_contents($stubFile);
        return $this->replacePlaceholders($template, $name);
    }

    private function getStubPath(string $type): string
    {
        return $this->stubPath . $type . '.stub';
    }

    private function replacePlaceholders(string $template, string $name): string
    {
        $className = $this->formatClassName($name);
        $tableName = $this->getTableName($name);

        return str_replace(
            ['{{class}}', '{{table}}'],
            [$className, $tableName],
            $template
        );
    }

    private function getMigrationsPath(): string
    {
        return dirname(__DIR__, 5) . '/database/migrations';
    }

    private function formatClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }

    private function getTableName(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }

    private function run(): void
    {
        $this->info("Executando migrações pendentes...");
        // Implementação da execução das migrações
        $migrations = $this->getPendingMigrations();
        foreach ($migrations as $migration) {
            $this->runMigration($migration);
        }
        $this->success("Migrações concluídas!");
    }

    private function rollback(int $steps = 1): void
    {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationsToRollback = array_slice(array_reverse($executedMigrations), 0, $steps);

        if (empty($migrationsToRollback)) {
            $this->info("Nenhuma migração para reverter.");
            return;
        }

        foreach ($migrationsToRollback as $migration) {
            $this->rollbackMigration($migration);
        }

        $this->success("Rollback de $steps migração(ões) concluído!");
    }

    private function reset(): void
    {
        $this->info("Revertendo todas as migrações...");

        $executedMigrations = array_reverse($this->getExecutedMigrations());

        foreach ($executedMigrations as $migration) {
            $this->rollbackMigration($migration);
        }

        // Limpa a tabela de migrações
        $this->connection->exec("TRUNCATE TABLE migrations");

        $this->success("Todas as migrações foram revertidas!");
    }
    private function refresh(): void
    {
        $this->reset();
        $this->run();
    }

    private function status(): void
    {
        $this->info("\nStatus das Migrações:");
        $this->info(str_repeat('-', 60));

        $migrationFiles = glob($this->getMigrationsPath() . '/*.php');
        $executedMigrations = $this->getExecutedMigrations();

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            $status = in_array($filename, $executedMigrations) ? 'Executada' : 'Pendente';
            $this->info(sprintf("%-50s [%s]", $filename, $status));
        }
        $this->info(str_repeat('-', 60));
    }

    private function getPendingMigrations(): array
    {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = glob($this->getMigrationsPath() . '/*.php');
        $pendingMigrations = [];

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executedMigrations)) {
                $pendingMigrations[] = $filename;
            }
        }

        sort($pendingMigrations); // Ordena por timestamp
        return $pendingMigrations;
    }

    private function runMigration(string $migrationFile): void
    {
        require_once $this->getMigrationsPath() . '/' . $migrationFile;

        $className = $this->getMigrationClassName($migrationFile);

        if (!class_exists($className)) {
            throw new \RuntimeException("Classe de migração não encontrada: $className");
        }

        try {
            $migration = new $className($this->connection);
            $migration->up();

            // Registra a migração como executada
            $batch = $this->getNextBatchNumber();
            $stmt = $this->connection->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migrationFile, $batch]);

            $this->success("Migração executada: $migrationFile");
        } catch (\Exception $e) {
            throw new \RuntimeException("Erro ao executar migração $migrationFile: " . $e->getMessage());
        }
    }

    private function rollbackMigration(string $migrationFile): void
    {
        require_once $this->getMigrationsPath() . '/' . $migrationFile;

        $className = $this->getMigrationClassName($migrationFile);

        if (!class_exists($className)) {
            throw new \RuntimeException("Classe de migração não encontrada: $className");
        }

        try {
            $migration = new $className($this->connection);
            $migration->down();

            // Remove o registro da migração
            $stmt = $this->connection->prepare("DELETE FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationFile]);

            $this->success("Migração revertida: $migrationFile");
        } catch (\Exception $e) {
            throw new \RuntimeException("Erro ao reverter migração $migrationFile: " . $e->getMessage());
        }
    }

    private function showHelp(): void
    {
        $this->info("Uso: migration <ação> [nome]");
        $this->info("Ações:");
        $this->info("  create <nome>      Cria uma nova migração");
        $this->info("  run               Executa as migrações pendentes");
        $this->info("  rollback [n]      Reverte as últimas n migrações");
        $this->info("  reset             Reverte todas as migrações");
        $this->info("  refresh           Reverte e executa todas as migrações");
        $this->info("  status            Mostra o status das migrações");
    }

    private function getExecutedMigrations(): array
    {
        try {
            // Verifica se a tabela migrations existe
            $this->connection->query("SELECT 1 FROM migrations LIMIT 1");
        } catch (\PDOException $e) {
            // Se não existir, cria a tabela
            $this->connection->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
            return [];
        }

        $stmt = $this->connection->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query("SELECT MAX(batch) FROM migrations");
        $lastBatch = $stmt->fetchColumn();
        return $lastBatch ? $lastBatch + 1 : 1;
    }

    private function getMigrationClassName(string $migrationFile): string
    {
        $filename = pathinfo($migrationFile, PATHINFO_FILENAME);
        // Remove o timestamp do nome do arquivo (YYYY_MM_DD_HHMMSS_)
        $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        return $this->formatClassName($className);
    }

    private function success(string $message): void
    {
        $this->info($message);
    }

    private function info(string $message): void
    {
        echo $message . PHP_EOL;
    }
}