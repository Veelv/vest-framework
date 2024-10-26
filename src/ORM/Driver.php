<?php
namespace Vest\ORM;

use PDO;

interface Driver
{
    public function connect(array $config): PDO;
    public function buildDsn(array $config): string;
}