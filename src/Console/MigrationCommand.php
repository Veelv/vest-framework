<?php

namespace Vest\Console;

use PDO;
use Vest\ORM\QueryBuilder;
use Vest\Exceptions\DatabaseQueryException;
use Vest\Exceptions\QueryBuilderException;
use Vest\ORM\Migration;
use Vest\ORM\Schema\Schema;

class MigrationCommand extends Command
{
    protected $name = 'migration';
    protected $description = 'Manages database migrations';
    protected $stubPath;
    private static ?PDO $connection = null;

    public function __construct()
    {
        $this->stubPath = __DIR__ . '/stubs/';
    }

    public static function setConnection(PDO $connection)
    {
        self::$connection = $connection;
        Migration::setConnection($connection);
        $queryBuilder = new QueryBuilder($connection);
        // Schema::setQueryBuilder($queryBuilder);
    }

    private function getQueryBuilder(): QueryBuilder
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Database connection not set');
        }
        return new QueryBuilder(self::$connection);
    }

    public function execute(array $args): void
    {
        if (empty($args)) {
            $this->showHelp();
            return;
        }

        $action = $args[0];

        try {
            switch ($action) {
                case 'create':
                    if (empty($args[1])) {
                        throw new \InvalidArgumentException("Migration name is required");
                    }
                    $this->create($args[1]);
                    break;

                case 'run':
                    // Only check/create the migrations table when running migrations
                    $this->createMigrationsTable();
                    $this->run();
                    break;

                case 'rollback':
                    $this->createMigrationsTable();
                    $steps = isset($args[1]) ? (int)$args[1] : 1;
                    $this->rollback($steps);
                    break;

                case 'reset':
                    $this->createMigrationsTable();
                    $this->reset();
                    break;

                case 'refresh':
                    $this->createMigrationsTable();
                    $this->refresh();
                    break;

                case 'fresh':
                    $this->createMigrationsTable();
                    $this->fresh();
                    break;

                case 'status':
                    $this->createMigrationsTable();
                    $this->status();
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid action: $action");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function create(string $name): void
    {
        $timestamp = date('Y_m_d_His');
        $className = $this->formatClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $path = $this->getMigrationsPath() . "/$filename";

        $stubContent = file_get_contents($this->stubPath . 'migration.stub');
        $stubContent = str_replace(
            ['{{class}}', '{{table}}'],
            [$className, $this->getTableName($name)],
            $stubContent
        );

        if (!is_dir($this->getMigrationsPath())) {
            mkdir($this->getMigrationsPath(), 0755, true);
        }

        if (file_put_contents($path, $stubContent)) {
            $this->success("Migration created: $filename");
        } else {
            throw new \RuntimeException("Error creating migration");
        }
    }

    private function createMigrationsTable(): void
    {
        $this->info("Checking if migrations table exists...");
        if (!$this->tableExists('migrations')) {
            $this->info("Table doesn't exist, creating...");
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            try {
                $this->getQueryBuilder()->rawQuery($sql);
                $this->success("'migrations' table created successfully!");
            } catch (\PDOException $e) {
                throw new DatabaseQueryException(
                    "Error creating 'migrations' table",
                    $sql,
                    [],
                    $e
                );
            }
        } else {
            $this->info("Migrations table already exists.");
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = self::$connection->query("SHOW TABLES LIKE '$tableName'");
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function getExecutedMigrations(): array
    {
        return $this->getQueryBuilder()
            ->table('migrations')
            ->select(['migration'])
            ->orderBy('id', 'ASC')
            ->get();
    }

    private function run(): void
    {
        $this->info("Running pending migrations...");
        $migrations = $this->getPendingMigrations();

        $this->info("Pending migrations found: " . implode(", ", $migrations));

        if (empty($migrations)) {
            $this->info("No pending migrations found.");
            return;
        }

        $batch = $this->getNextBatchNumber();
        $this->info("Next batch number: $batch");

        foreach ($migrations as $migration) {
            $this->info("Attempting to run migration: $migration");
            $this->runMigration($migration, $batch);
        }
        $this->success("Migrations completed!");
    }

    private function rollback(int $steps = 1): void
    {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationsToRollback = array_slice(array_reverse($executedMigrations), 0, $steps);

        if (empty($migrationsToRollback)) {
            $this->info("No migrations to rollback.");
            return;
        }

        foreach ($migrationsToRollback as $migration) {
            $this->rollbackMigration($migration['migration']);
        }

        $this->success("Rollback of $steps migration(s) completed!");
    }

    private function reset(): void
    {
        $this->info("Reverting all migrations...");
        $executedMigrations = array_reverse($this->getExecutedMigrations());

        foreach ($executedMigrations as $migration) {
            $this->rollbackMigration($migration['migration']);
        }

        $this->getQueryBuilder()
            ->table('migrations')
            ->delete();

        $this->success("All migrations have been reverted!");
    }

    private function refresh(): void
    {
        $this->reset();
        $this->run();
    }

    private function fresh(): void
    {
        $this->info("Starting fresh migration...");

        // Get all existing tables through QueryBuilder
        $queryBuilder = $this->getQueryBuilder();

        // First, remove all migrations from the migrations table
        $queryBuilder->table('migrations')->delete();

        // Then run all migrations again
        $this->run();

        $this->success("Fresh migration completed successfully!");
    }

    private function status(): void
    {
        $this->info("\nMigrations Status:");
        $this->info(str_repeat('-', 60));

        $migrationFiles = glob($this->getMigrationsPath() . '/*.php');
        $executedMigrations = array_column($this->getExecutedMigrations(), 'migration');

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            $status = in_array($filename, $executedMigrations) ? 'Executed' : 'Pending';
            $this->info(sprintf("%-50s [%s]", $filename, $status));
        }
        $this->info(str_repeat('-', 60));
    }

    private function getStubContents(string $type, string $name): string
    {
        $stubFile = $this->getStubPath($type);

        if (!file_exists($stubFile)) {
            throw new \RuntimeException("Template not found: $stubFile");
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

    private function getTableName(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }

    private function getPendingMigrations(): array
    {
        $executedMigrations = array_column($this->getExecutedMigrations(), 'migration');
        $migrationFiles = glob($this->getMigrationsPath() . '/*.php');
        $pendingMigrations = [];

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (!in_array($filename, $executedMigrations)) {
                $pendingMigrations[] = $filename;
            }
        }

        sort($pendingMigrations);
        return $pendingMigrations;
    }

    private function runMigration(string $migrationFile, int $batch): void
    {
        require_once $this->getMigrationsPath() . '/' . $migrationFile;

        $className = $this->getMigrationClassName($migrationFile);

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: $className");
        }

        try {
            $migration = new $className();
            $migration->up();

            $this->getQueryBuilder()
                ->table('migrations')
                ->insert([
                    'migration' => $migrationFile,
                    'batch' => $batch,
                    'executed_at' => date('Y-m-d H:i:s')
                ]);

            $this->success("Migration executed: $migrationFile");
        } catch (\Exception $e) {
            throw new \RuntimeException("Error executing migration $migrationFile: " . $e->getMessage());
        }
    }

    private function rollbackMigration(string $migrationFile): void
    {
        require_once $this->getMigrationsPath() . '/' . $migrationFile;

        $className = $this->getMigrationClassName($migrationFile);

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: $className");
        }

        try {
            $migration = new $className();
            $migration->down();

            $this->getQueryBuilder()
                ->table('migrations')
                ->where('migration', '=', $migrationFile)
                ->delete();

            $this->success("Migration rolled back: $migrationFile");
        } catch (\Exception $e) {
            throw new \RuntimeException("Error rolling back migration $migrationFile: " . $e->getMessage());
        }
    }

    private function getNextBatchNumber(): int
    {
        $lastBatch = $this->getQueryBuilder()
            ->table('migrations')
            ->select(['batch'])
            ->orderBy('id', 'DESC')
            ->get()[0]['batch'] ?? 0;

        return $lastBatch + 1;
    }

    private function getMigrationClassName(string $migrationFile): string
    {
        $filename = pathinfo($migrationFile, PATHINFO_FILENAME);
        $parts = explode('_', $filename);

        // Ignore the first 4 elements that form the timestamp
        $className = implode('_', array_slice($parts, 4));

        return $this->formatClassName($className);
    }

    private function getMigrationsPath(): string
    {
        return dirname(__DIR__, 5) . '/database/migrations';
    }

    private function formatClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }

    private function showHelp(): void
    {
        $this->info("Usage: migration <action> [name]");
        $this->info("Actions:");
        $this->info("  create <name>      Creates a new migration");
        $this->info("  run               Runs pending migrations");
        $this->info("  rollback [n]      Rolls back the last n migrations");
        $this->info("  reset             Rolls back all migrations");
        $this->info("  refresh           Rolls back and runs all migrations");
        $this->info("  fresh             Clears and runs all migrations");
        $this->info("  status            Shows the status of migrations");
    }

    private function success(string $message): void
    {
        $this->info($message);
    }

    private function info(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function error(string $message): void
    {
        echo "Error: $message" . PHP_EOL;
    }
}
