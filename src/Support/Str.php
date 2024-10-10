<?php

namespace Vest\Support;

class Str
{
    /**
     * Converte uma string para snake_case.
     *
     * @param string $value
     * @return string
     */
    public static function snake(string $value): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $value));
    }

    /**
     * Converte uma string para kebab-case.
     *
     * @param string $value
     * @return string
     */
    public static function kebab(string $value): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-$0', $value));
    }

    /**
     * Verifica se a string contém outra string.
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Capitaliza a primeira letra da string.
     *
     * @param string $value
     * @return string
     */
    public static function capitalize(string $value): string
    {
        return ucfirst($value);
    }

    /**
     * Remove espaços em branco do início e do fim da string.
     *
     * @param string $value
     * @return string
     */
    public static function trim(string $value): string
    {
        return trim($value);
    }

    /**
     * Gera um UUID v4.
     *
     * @return string
     */
    public static function uuid(): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
    }

    /**
     * Verifica se a string é um e-mail válido.
     *
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Divide uma string em um array com base em um delimitador.
     *
     * @param string $value
     * @param string $delimiter
     * @return array
     */
    public static function split(string $value, string $delimiter): array
    {
        return explode($delimiter, $value);
    }

    /**
     * Compara duas strings, ignorando maiúsculas/minúsculas.
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public static function equalsIgnoreCase(string $str1, string $str2): bool
    {
        return strcasecmp($str1, $str2) === 0;
    }

    /**
     * Trunca uma string para um tamanho máximo, adicionando '...' se truncada.
     *
     * @param string $value
     * @param int $maxLength
     * @return string
     */
    public static function truncate(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }
        return substr($value, 0, $maxLength - 3) . '...';
    }

    /**
     * Capitaliza a primeira letra de cada palavra na string.
     *
     * @param string $value
     * @return string
     */
    public static function titleCase(string $value): string
    {
        return ucwords(strtolower($value));
    }
}