<?php

namespace Vest\Console\Makers;

interface ComponentMaker
{
    public function make(string $name, array $options): void;
}