<?php

namespace Vest\ORM\Cache;

class TaggableCache
{
    protected $cache;
    protected $tags = [];

    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    public function tag($tag)
    {
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }
        return $this;
    }

    public function put($key, $value, $ttl)
    {
        $this->cache->set($key, $value, $ttl);
        foreach ($this->tags as $tag => $keys) {
            $this->tags[$tag][] = $key;
        }
    }

    public function forget($key)
    {
        $this->cache->delete($key);
        foreach ($this->tags as $tag => $keys) {
            if (in_array($key, $keys)) {
                $this->tags[$tag] = array_diff($this->tags[$tag], [$key]);
            }
        }
    }

    public function invalidateTag($tag)
    {
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $key) {
                $this->cache->delete($key);
            }
            unset($this->tags[$tag]);
        }
    }
}