<?php

namespace Vest\Routing;

use Psr\SimpleCache\CacheInterface;

/**
 * Class RouteCache
 * 
 * A simple cache implementation for routing, adhering to PSR-16 Simple Cache Interface.
 * Caches route data with an optional Time-to-Live (TTL) to optimize routing performance.
 */
class RouteCache implements CacheInterface
{
    /**
     * @var array Cache storage array to hold cached items.
     */
    protected array $cache = [];

    /**
     * @var int Default Time-to-Live (TTL) in seconds. Defaults to 1 hour (3600 seconds).
     */
    protected int $ttl = 3600;

    /**
     * Retrieve an item from the cache by key.
     * 
     * @param string $key Cache item key.
     * @param mixed $default Default value to return if the key does not exist or is expired.
     * @return mixed Cached value or the default value if the key is not found or expired.
     */
    public function get(string $key, $default = null): mixed
    {
        if ($this->has($key) && !$this->isExpired($key)) {
            return $this->cache[$key]['value'];
        }

        $this->delete($key); // Delete if expired
        return $default;
    }

    /**
     * Store an item in the cache with an optional TTL.
     * 
     * @param string $key Cache item key.
     * @param mixed $value Cache item value.
     * @param null|int $ttl Optional TTL for the cache item. Defaults to the class TTL.
     * @return bool True on success, false on failure.
     */
    public function set(string $key, mixed $value, $ttl = null): bool
    {
        $expires = time() + ($ttl ?? $this->ttl); // Calculate expiration time
        $this->cache[$key] = [
            'value' => $value,
            'expires' => $expires
        ];
        return true;
    }

    /**
     * Alias for the set method to store an item in the cache.
     * 
     * @param string $key Cache item key.
     * @param mixed $value Cache item value.
     * @param null|int $ttl Optional TTL for the cache item.
     * @return bool True on success.
     */
    public function put(string $key, mixed $value, $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }

    /**
     * Delete an item from the cache by key.
     * 
     * @param string $key Cache item key to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key): bool
    {
        unset($this->cache[$key]); // Remove from cache
        return true;
    }

    /**
     * Clears all items from the cache.
     * 
     * @return bool True on success, false on failure.
     */
    public function clear(): bool
    {
        $this->cache = []; // Reset the cache
        return true;
    }

    /**
     * Retrieve multiple cache items by their keys.
     * 
     * @param iterable $keys A list of keys that will be retrieved.
     * @param mixed $default Default value to return if a key does not exist or is expired.
     * @return iterable An array of key-value pairs. If a key is not found, the default value will be returned.
     */
    public function getMultiple(iterable $keys, $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default); // Fetch each key
        }
        return $result;
    }

    /**
     * Store multiple items in the cache at once.
     * 
     * @param iterable $values An array of key-value pairs to store in the cache.
     * @param null|int $ttl Optional TTL for all items.
     * @return bool True on success, false on failure.
     */
    public function setMultiple(iterable $values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl); // Set each key-value pair
        }
        return true;
    }

    /**
     * Delete multiple cache items by their keys.
     * 
     * @param iterable $keys A list of keys to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key); // Delete each key
        }
        return true;
    }

    /**
     * Check if an item exists in the cache and is not expired.
     * 
     * @param string $key Cache item key.
     * @return bool True if the key exists and has not expired, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]) && !$this->isExpired($key); // Check if the item exists and is valid
    }

    /**
     * Check if a cache item has expired based on its expiration timestamp.
     * 
     * @param string $key Cache item key.
     * @return bool True if the item has expired, false otherwise.
     */
    public function isExpired(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return true; // If the key doesn't exist, consider it expired
        }

        return time() >= $this->cache[$key]['expires']; // Compare current time with expiration time
    }

    /**
     * Set the default Time-to-Live (TTL) for cache items.
     * 
     * @param int $ttl TTL in seconds.
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }
}