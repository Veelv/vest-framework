<?php

namespace Vest\ORM\Cache;

class ModelCache
{
    protected $cache;

    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    public function get($key)
    {
        return $this->cache->get($key);
    }

    public function put($key, $value)
    {
        $this->cache->set($key, $value);
    }

    public function forget($key)
    {
        $this->cache->delete($key);
    }
}