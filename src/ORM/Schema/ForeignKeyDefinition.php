<?php
namespace Vest\ORM\Schema;

class ForeignKeyDefinition
{
    protected Blueprint $blueprint;
    protected string $columns;
    protected ?string $table = null;
    protected ?string $references = null;
    protected string $onDelete = 'RESTRICT';
    protected string $onUpdate = 'RESTRICT';

    public function __construct(Blueprint $blueprint, $columns)
    {
        $this->blueprint = $blueprint;
        $this->columns = $columns;
    }

    public function references($columns): self
    {
        $this->references = $columns;
        return $this;
    }

    public function on($table): self
    {
        $this->table = $table;
        return $this;
    }

    public function onDelete($action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate($action): self
    {
        $this->onUpdate = $action;
        return $this;
    }
}