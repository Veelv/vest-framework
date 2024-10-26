<?php
namespace Vest\Debug;

class Log
{
    protected static ?Logger $instance = null;

    /**
     * Obtém a instância do Logger
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    /**
     * Método mágico para chamar métodos do Logger
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->$name(...$arguments);
    }
}