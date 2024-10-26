<?php

namespace Vest\Support;

use ReflectionClass;
use Exception;

class ControllerFactory
{
    protected string $baseNamespace = 'App\Controllers';
    protected array $instances = []; // Cache de instâncias de controladores

    public function make(string $controllerClass)
    {
        // Verifica se a classe já está com namespace completo
        $fullClass = strpos($controllerClass, '\\') === false
            ? $this->baseNamespace . '\\' . $controllerClass
            : $controllerClass;

        if (!class_exists($fullClass)) {
            throw new Exception("Controller [$fullClass] not found.");
        }

        // Se a instância já estiver no cache, retorne-a
        if (isset($this->instances[$fullClass])) {
            return $this->instances[$fullClass];
        }

        // Use reflexão para resolver dependências automaticamente
        $reflection = new ReflectionClass($fullClass);
        if (!$reflection->isInstantiable()) {
            throw new Exception("Controller [$fullClass] is not instantiable.");
        }

        // Resolver dependências do construtor
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            $instance = $reflection->newInstance();
        } else {
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);
            $instance = $reflection->newInstanceArgs($dependencies);
        }

        // Armazena a instância no cache
        $this->instances[$fullClass] = $instance;

        return $instance;
    }

    protected function resolveDependencies(array $parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type === null) {
                // Se não houver tipo, adiciona null
                $dependencies[] = null;
                continue;
            }

            $className = $type->getName();
            if (!class_exists($className)) {
                throw new Exception("Dependency [$className] not found.");
            }

            // Resolve a dependência recursivamente
            $dependencies[] = $this->make($className);
        }

        return $dependencies;
    }
}