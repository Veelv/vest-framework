<?php

namespace Vest\Validation;

use Vest\Exceptions\HttpException;

interface ValidatorInterface
{
    public function errors(): array;
}

class Validator implements ValidatorInterface
{
    protected array $data;
    protected array $errors = [];
    protected array $customMessages = [];
    protected ValidationRules $validationRules;

    public function __construct(?array $data)
    {
        $this->data = $data ?? [];
        $this->validationRules = new ValidationRules();
    }

    public function validate(array $rules, array $messages = []): bool
    {
        $this->customMessages = $messages;

        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $parameters = $this->extractParameters($rule);

                if (!$this->hasError($field)) {
                    $this->applyRule($field, $parameters['name'], $parameters['args']);
                }
            }

            // Verifica se houve erro e adiciona a mensagem personalizada, se existir
            if ($this->hasError($field)) {
                $defaultMessage = "Mensagem padrão não especificada.";
                $customMessage = $this->getCustomMessage($field, $parameters['name'], $defaultMessage);
                $this->addError($field, $customMessage);
            }
        }

        return !$this->fails();
    }

    protected function applyRule(string $field, string $rule, array $parameters = []): void
    {
        if (method_exists($this->validationRules, $rule)) {
            $this->validationRules->$rule($field, $this->data[$field] ?? null, $this, ...$parameters);
        } else {
            throw new \InvalidArgumentException("Regra de validação '$rule' não encontrada.");
        }
    }

    protected function extractParameters(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $name = trim($parts[0]);
        $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $args = array_map(function ($arg) {
            return is_numeric($arg) ? (int)$arg : $arg;
        }, $args);

        return ['name' => $name, 'args' => array_map('trim', $args)];
    }

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    protected function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        $formattedErrors = [];
        foreach ($this->errors as $field => $message) {
            $formattedErrors[$field] = $message;
        }
        return $formattedErrors;
    }

    public function setCustomMessage(string $field, string $rule, string $message): void
    {
        $this->customMessages["{$field}.{$rule}"] = $message;
    }

    protected function getCustomMessage(string $field, string $rule, string $defaultMessage): string
    {
        return $this->customMessages["{$field}.{$rule}"] ?? $defaultMessage;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
