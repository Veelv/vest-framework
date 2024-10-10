<?php

namespace Vest\Validation;

use Vest\Exceptions\HttpException;

class Validator
{
    protected array $data;
    protected array $errors = [];
    protected array $customMessages = [];
    protected ValidationRules $validationRules;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validationRules = new ValidationRules();
    }

    /**
     * Valida os dados de entrada com as regras especificadas.
     *
     * @param array $rules Regras de validação.
     * @throws HttpException Se houver erros de validação.
     */
    public function validate(array $rules): void
    {
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $parameters = $this->extractParameters($rule);
                $this->applyRule($field, $parameters['name'], $parameters['args']);
            }
        }

        if ($this->fails()) {
            throw new HttpException(implode(', ', $this->errors), 400);
        }
    }

    /**
     * Aplica uma regra de validação específica a um campo.
     *
     * @param string $field Nome do campo.
     * @param string $rule Nome da regra.
     * @param array $parameters Argumentos adicionais da regra.
     */
    protected function applyRule(string $field, string $rule, array $parameters = []): void
    {
        if (method_exists($this->validationRules, $rule)) {
            $this->validationRules->$rule($field, $this->data[$field] ?? null, $this, ...$parameters);
        } else {
            $this->addError("A regra de validação '{$rule}' não foi reconhecida.");
        }
    }

    /**
     * Extrai parâmetros de uma regra de validação.
     *
     * @param string $rule Regra de validação.
     * @return array Nome da regra e seus parâmetros.
     */
    protected function extractParameters(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        $name = trim($parts[0]);
        $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

        return ['name' => $name, 'args' => array_map('trim', $args)];
    }

    /**
     * Adiciona uma mensagem de erro ao validador.
     *
     * @param string $message Mensagem de erro.
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Verifica se a validação falhou.
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retorna as mensagens de erro acumuladas.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Define uma mensagem de erro personalizada para uma regra específica.
     *
     * @param string $field Nome do campo.
     * @param string $rule Nome da regra.
     * @param string $message Mensagem de erro personalizada.
     */
    public function setCustomMessage(string $field, string $rule, string $message): void
    {
        $this->customMessages["{$field}.{$rule}"] = $message;
    }

    /**
     * Recupera uma mensagem personalizada, se disponível, ou retorna a mensagem padrão.
     *
     * @param string $field Nome do campo.
     * @param string $rule Nome da regra.
     * @param string $defaultMessage Mensagem padrão.
     * @return string
     */
    protected function getCustomMessage(string $field, string $rule, string $defaultMessage): string
    {
        return $this->customMessages["{$field}.{$rule}"] ?? $defaultMessage;
    }
}