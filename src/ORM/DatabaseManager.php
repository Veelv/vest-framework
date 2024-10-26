<?php
namespace Vest\ORM;

use Exception;

class DatabaseManager {
    private $mysqlConnection;
    private $pgsqlConnection;
    private $sqliteConnection;
    private $sqlsrvConnection;

    public function __construct($mysqlConnection, $pgsqlConnection, $sqliteConnection, $sqlsrvConnection) {
        $this->mysqlConnection = $mysqlConnection;
        $this->pgsqlConnection = $pgsqlConnection;
        $this->sqliteConnection = $sqliteConnection;
        $this->sqlsrvConnection = $sqlsrvConnection;
    }

    public function executeQuery($query, $connection = 'mysql') {
        switch ($connection) {
            case 'mysql':
                return $this->mysqlConnection->getConnection()->query($query);
            case 'pgsql':
                return $this->pgsqlConnection->getConnection()->query($query);
            case 'sqlite':
                return $this->sqliteConnection->getConnection()->query($query);
            case 'sqlsrv':
                return $this->sqlsrvConnection->getConnection()->query($query);
            default:
                throw new Exception('Conexão não suportada');
        }
    }
}