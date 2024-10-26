<?php

namespace Vest\Validation;

use DateTime;
use PDO;
use Vest\ORM\QueryBuilder;

class ValidationRules
{
    private static PDO $connection;

    public static function setConnection(PDO $connection): void
    {
        self::$connection = $connection;
    }

    // Mensagens padrão
    protected static array $defaultMessages = [
        'required' => "The field '{field}' is required.",
        'max' => "The field '{field}' must not exceed {max} characters.",
        'min' => "The field '{field}' must have at least {min} characters.",
        'email' => "The field '{field}' must be a valid email address.",
        'numeric' => "The field '{field}' must be a numeric value.",
        'integer' => "The field '{field}' must be an integer.",
        'boolean' => "The field '{field}' must be a boolean value.",
        'date' => "The field '{field}' must be a valid date.",
        'dateFormat' => "The field '{field}' must be in the format {format}.",
        'in' => "The field '{field}' must be one of the following values: {allowedValues}.",
        'notIn' => "The field '{field}' cannot be any of the following values: {forbiddenValues}.",
        'regex' => "The field '{field}' does not match the expected format.",
        'alpha' => "The field '{field}' must contain only letters.",
        'alphaNum' => "The field '{field}' must contain only letters and numbers.",
        'alphaDash' => "The field '{field}' must contain only letters, numbers, dashes, and underscores.",
        'url' => "The field '{field}' must be a valid URL.",
        'ip' => "The field '{field}' must be a valid IP address.",
        'between' => "The field '{field}' must be between {min} and {max}.",
        'confirmed' => "The field '{field}' does not match the confirmation.",
        'different' => "The field '{field}' must be different from '{otherField}'.",
        'mimes' => "The file uploaded in '{field}' must be of type: {allowedMimes}.",
        'unique' => "The value of the field '{field}' is already in use.",
        'uppercase' => "The field '{field}' must contain at least one uppercase letter.",
        'lowercase' => "The field '{field}' must contain at least one lowercase letter.",
        'has_number' => "The field '{field}' must contain at least one number.",
        'specialCharacter' => "The field '{field}' must contain at least one special character.",
    ];

    public function setDefaultMessages(array $messages): void
    {
        self::$defaultMessages = array_merge(self::$defaultMessages, $messages);
    }

    public function required(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if ($value === null || $value === '') {
            $validator->addError($field, $this->getMessage('required', $field));
        }
    }

    public function max(string $field, $value, Validator $validator, string $max, string $customMessage = null): void
    {
        $max = (int)$max; // Converte o parâmetro $max para um inteiro

        if (is_string($value) && mb_strlen($value) > $max) {
            $validator->addError($field, $this->getMessage('max', $field, ['max' => $max]));
        } elseif (is_numeric($value) && $value > $max) {
            $validator->addError($field, $this->getMessage('max', $field, ['max' => $max]));
        }
    }

    public function min(string $field, $value, Validator $validator, int $min, string $customMessage = null): void
    {
        if (is_string($value) && mb_strlen($value) < $min) {
            $validator->addError($field, $this->getMessage('min', $field, ['min' => $min]));
        } elseif (is_numeric($value) && $value < $min) {
            $validator->addError($field, $this->getMessage('min', $field, ['min' => $min]));
        }
    }

    public function email(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validator->addError($field, $this->getMessage('email', $field));
        }
    }

    public function numeric(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!is_numeric($value)) {
            $validator->addError($field, $this->getMessage('numeric', $field));
        }
    }

    public function integer(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $validator->addError($field, $this->getMessage('integer', $field));
        }
    }

    public function boolean(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            $validator->addError($field, $this->getMessage('boolean', $field));
        }
    }

    public function date(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!strtotime($value)) {
            $validator->addError($field, $this->getMessage('date', $field));
        }
    }

    public function dateFormat(string $field, $value, Validator $validator, string $format, string $customMessage = null): void
    {
        $date = DateTime::createFromFormat($format, $value);
        if (!$date || $date->format($format) !== $value) {
            $validator->addError($field, $this->getMessage('dateFormat', $field, ['format' => $format]));
        }
    }

    public function in(string $field, $value, Validator $validator, array $allowedValues, string $customMessage = null): void
    {
        if (!in_array($value, $allowedValues, true)) {
            $allowedValuesString = implode(', ', $allowedValues);
            $validator->addError($field, $this->getMessage('in', $field, ['allowedValues' => $allowedValuesString]));
        }
    }

    public function notIn(string $field, $value, Validator $validator, array $forbiddenValues, string $customMessage = null): void
    {
        if (in_array($value, $forbiddenValues, true)) {
            $forbiddenValuesString = implode(', ', $forbiddenValues);
            $validator->addError($field, $this->getMessage('notIn', $field, ['forbiddenValues' => $forbiddenValuesString]));
        }
    }

    public function regex(string $field, $value, Validator $validator, string $pattern, string $customMessage = null): void
    {
        if (!preg_match($pattern, $value)) {
            $validator->addError($field, $this->getMessage('regex', $field));
        }
    }

    public function alpha(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!ctype_alpha($value)) {
            $validator->addError($field, $this->getMessage('alpha', $field));
        }
    }

    public function alphaNum(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!ctype_alnum($value)) {
            $validator->addError($field, $this->getMessage('alphaNum', $field));
        }
    }

    public function alphaDash(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $validator->addError($field, $this->getMessage('alphaDash', $field));
        }
    }

    public function url(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $validator->addError($field, $this->getMessage('url', $field));
        }
    }

    public function ip(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            $validator->addError($field, $this->getMessage('ip', $field));
        }
    }

    public function between(string $field, $value, Validator $validator, $min, $max, string $customMessage = null): void
    {
        if (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                $validator->addError($field, $this->getMessage('between', $field, ['min' => $min, 'max' => $max]));
            }
        } elseif (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                $validator->addError($field, $this->getMessage('between', $field, ['min' => $min, 'max' => $max]));
            }
        }
    }

    public function confirmed(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        $confirmation = $validator->getData()["{$field}_confirmation"] ?? null;
        if ($value !== $confirmation) {
            $validator->addError($field, $this->getMessage('confirmed', $field));
        }
    }

    public function different(string $field, $value, Validator $validator, string $otherField, string $customMessage = null): void
    {
        if ($value === $validator->getData()[$otherField]) {
            $validator->addError($field, $this->getMessage('different', $field, ['otherField' => $otherField]));
        }
    }

    public function mimes(string $field, $value, Validator $validator, array $allowedMimes, string $customMessage = null): void
    {
        $fileType = mime_content_type($value['tmp_name'] ?? '');
        if (!in_array($fileType, $allowedMimes)) {
            $allowedMimesString = implode(', ', $allowedMimes);
            $validator->addError($field, $this->getMessage('mimes', $field, ['allowedMimes' => $allowedMimesString]));
        }
    }

    public function unique(string $field, $value, Validator $validator, string $table, string $column, string $customMessage = null): void
    {
        if ($this->exists($table, $column, $value)) {
            $validator->addError($field, $customMessage ?? $this->getMessage('unique', $field));
        }
    }
    public function getMessage(string $key, string $field, array $replace = []): string
    {
        $message = self::$defaultMessages[$key] ?? "Validation error on '{field}'.";

        // Substitui as chaves de substituição pela mensagem
        $replace['field'] = $field;
        foreach ($replace as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }

        return $message;
    }

    public function exists(string $table, string $column, $value): bool
    {
        $queryBuilder = new QueryBuilder(self::$connection);
        $query = $queryBuilder->table($table)
            ->select([$column])
            ->where($column, '=', $value);

        $result = $query->get();

        return !empty($result);
    }

    public function uppercase(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!preg_match('/[A-Z]/', $value)) {
            $validator->addError($field, $this->getMessage('uppercase', $field));
        }
    }

    public function lowercase(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!preg_match('/[a-z]/', $value)) {
            $validator->addError($field, $this->getMessage('lowercase', $field));
        }
    }

    public function has_number(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!preg_match('/\d/', $value)) {
            $validator->addError($field, $this->getMessage('has_number', $field));
        }
    }

    public function specialCharacter(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        if (!preg_match('/[\W_]/', $value)) {
            $validator->addError($field, $this->getMessage('specialCharacter', $field));
        }
    }

    public function validatePassword(string $field, $value, Validator $validator, string $customMessage = null): void
    {
        $this->uppercase($field, $value, $validator, $customMessage);
        $this->lowercase($field, $value, $validator, $customMessage);
        $this->has_number($field, $value, $validator, $customMessage);
        $this->specialCharacter($field, $value, $validator, $customMessage);
    }
}
