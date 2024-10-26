<?php
namespace Vest\ORM;

use Vest\ORM\Connection;
use Vest\Exceptions\DatabaseConnectionException;

class ConnectionFactory
{
    public function createConnection(array $config)
    {
        // Set default driver if not specified
        $driver = $config['driver'] ?? 'mysql';

        // Validate the driver
        if (!in_array($driver, ['mysql', 'pgsql', 'sqlite', 'sqlsrv'])) {
            throw new DatabaseConnectionException("Driver de banco de dados inválido: {$driver}");
        }

        // Validate required configuration settings
        if (!isset($config['host']) || !isset($config['database']) || !isset($config['username']) || !isset($config['password'])) {
            throw new DatabaseConnectionException("Configurações de conexão inválidas");
        }

        // Remove driver from config to avoid passing it to the connection
        unset($config['driver']);

        // Dynamically create the driver instance
        $driverClass = "Vest\\ORM\\{$driver}Driver";
        if (!class_exists($driverClass)) {
            throw new DatabaseConnectionException("Driver class não encontrado: {$driverClass}");
        }
        $driverInstance = new $driverClass();

        // Return a new Connection instance with the config and driver
        try {
            $connection = new Connection($config, $driverInstance);
            return $connection;
        } catch (\PDOException $e) {
            throw new DatabaseConnectionException("Erro de conexão com o banco de dados: " . $e->getMessage(), $e->getCode());
        }
    }
}