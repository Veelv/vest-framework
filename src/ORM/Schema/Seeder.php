<?php
namespace Vest\ORM\Schema;

use Vest\ORM\QueryBuilder;

abstract class Seeder
{
    protected QueryBuilder $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    abstract public function run(): void;

    protected function insert(string $table, array $data): void
    {
        $this->queryBuilder->table($table)->insert($data);
    }
}