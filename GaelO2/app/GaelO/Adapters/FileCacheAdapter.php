<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\CacheInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class FileCacheAdapter implements CacheInterface
{

    public function get(string $key)
    {
        return Cache::store(Config::get('cache.file-cache'))->get($key);
    }

    public function store(string $key, $value): bool
    {
        Cache::store(Config::get('cache.file-cache'))->put($key, $value);
        return true;
    }

    public function delete(string $key): bool
    {
        Cache::store(Config::get('cache.file-cache'))->forget($key);
        return true;
    }
}
