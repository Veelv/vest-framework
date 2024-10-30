<?php

namespace Vest\ORM\Cache;

class QueryCache
{
    protected $cache;

    public function __construct($cache)
    {
        $this->cache = $cache; // Ex: Redis, Memcached
    }

    public function get($key)
    {
        return $this->cache->get($key);
    }

    public function put($key, $value, $ttl)
    {
        $this->cache->set($key, $value, $ttl);
    }

    public function forget($key)
    {
        $this->cache->delete($key);
    }
}