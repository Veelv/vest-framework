<?php

namespace Vest\Validation;

use Vest\ORM\Connection;
use Vest\ORM\QueryBuilder;

class ValidationRules
{
    public function required(string $field, $value, Validator $validator): void
    {
        if (empty($value)) {
            $validator->addError("O campo '{$field}' é obrigatório.");
        }
    }

    public function max(string $field, $value, Validator $validator, int $max): void
    {
        if (strlen($value) > $max) {
            $validator->addError("O campo '{$field}' deve ter no máximo {$max} caracteres.");
        }
    }

    public function min(string $field, $value, Validator $validator, int $min): void
    {
        if (strlen($value) < $min) {
            $validator->addError("O campo '{$field}' deve ter pelo menos {$min} caracteres.");
        }
    }

    public function email(string $field, $value, Validator $validator): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validator->addError("O campo '{$field}' deve ser um email válido.");
        }
    }

    public function unique(string $field, $value, Validator $validator, string $table, string $column = null): void
    {
        $column = $column ?? $field;

        // Assuming you have a method to get the config or pass it to the constructor
        $config = $this->getDatabaseConfig(); // Replace with the actual method to get config

        $connection = new Connection($config);
        $query = new QueryBuilder($connection->getConnection()); // Make sure to pass the PDO connection

        // Execute the query to check for unique value
        $result = $query->table($table)->where($column, '=', $value)->get();

        // Handle the result accordingly
        if (!empty($result)) {
            // Handle the case where the value is not unique
            $validator->addError($field, 'The value must be unique.');
        }
    }
}
