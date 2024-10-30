<?php

namespace Vest\ORM;

class PolymorphicModel extends BaseModel
{
    protected string $morphId;
    protected string $morphType;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->morphId = $attributes['morph_id'];
        $this->morphType = $attributes['morph_type'];
    }
}