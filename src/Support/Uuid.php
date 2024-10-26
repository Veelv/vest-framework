<?php
namespace Vest\Support;

use InvalidArgumentException;

class Uuid
{
    /**
     * Generates a new UUID v4
     *
     * @return string
     */
    public static function generate(): string
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validates a UUID string
     *
     * @param string $uuid
     * @return bool
     */
    public static function isValid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid) === 1;
    }

    /**
     * Converts a UUID to a hexadecimal string
     *
     * @param string $uuid
     * @return string
     */
    public static function toHex(string $uuid): string
    {
        return str_replace('-', '', $uuid);
    }

    /**
     * Converts a hexadecimal string to a UUID
     *
     * @param string $hex
     * @return string
     */
    public static function fromHex(string $hex): string
    {
        return implode('-', str_split($hex, 4)).implode('-', str_split(substr($hex, 8), 4)).implode('-', str_split(substr($hex, 13), 4)).implode('-', str_split(substr($hex, 18), 4));
    }

    /**
     * Generates a new UUID v4 with a specific namespace
     *
     * @param string $namespace
     * @return string
     */
    public static function generateWithNamespace(string $namespace): string
    {
        $hash = hash('sha256', $namespace, true);
        $uuid = self::generate();
        $uuid = str_replace(substr($uuid, 0, 8), bin2hex(substr($hash, 0, 4)), $uuid);
        return $uuid;
    }

    /**
     * Generates a new UUID v4 with a specific name
     *
     * @param string $name
     * @return string
     */
    public static function generateWithName(string $name): string
    {
        $hash = hash('sha256', $name, true);
        $uuid = self::generate();
        $uuid = str_replace(substr($uuid, 0, 8), bin2hex(substr($hash, 0, 4)), $uuid);
        return $uuid;
    }

    /**
     * Extracts the version from a UUID
     *
     * @param string $uuid
     * @return int
     */
    public static function getVersion(string $uuid): int
    {
        $uuid = str_replace('-', '', $uuid);
        $version = hexdec(substr($uuid, 12, 1));
        return ($version >> 4) & 0xf;
    }

    /**
     * Extracts the variant from a UUID
     *
     * @param string $uuid
     * @return int
     */
    public static function getVariant(string $uuid): int
    {
        $uuid = str_replace('-', '', $uuid);
        $variant = hexdec(substr($uuid, 12, 1));
        return $variant & 0x3f;
    }

    /**
     * Checks if a UUID is a v4 UUID
     *
     * @param string $uuid
     * @return bool
     */
    public static function isV4(string $uuid): bool
    {
        return self::getVersion($uuid) === 4;
    }

    /**
     * Checks if a UUID is a v5 UUID
     *
     * @param string $uuid
     * @return bool
     */
    public static function isV5(string $uuid): bool
    {
        return self::getVersion($uuid) === 5;
    }

    /**
     * Throws an exception if the UUID is invalid
     *
     * @param string $uuid
     * @throws InvalidArgumentException
     */
    public static function validate(string $uuid): void
    {
        if (!self::isValid($uuid)) {
            throw new InvalidArgumentException("Invalid UUID: $uuid");
        }
    }
}