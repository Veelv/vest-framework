<?php

namespace Vest\ORM\Cache;

class CacheManager
{
    protected $queryCache;
    protected $modelCache;
    protected $taggableCache;

    public function __construct($queryCache, $modelCache, $taggableCache)
    {
        $this->queryCache = $queryCache;
        $this->modelCache = $modelCache;
        $this->taggableCache = $taggableCache;
    }

    public function getQueryCache()
    {
        return $this->queryCache;
    }

    public function getModelCache()
    {
        return $this->modelCache;
    }

    public function getTaggableCache()
    {
        return $this->taggableCache;
    }
}