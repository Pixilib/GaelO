<?php

namespace App\GaelO\Interfaces\Adapters;

interface CacheInterface
{
    public function store(string $key, $value): bool;
    public function get(string $key);
    public function delete(string $key): bool;
}
