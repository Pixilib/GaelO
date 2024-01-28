<?php

namespace App\GaelO\Adapters;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelONotFoundException;
use Illuminate\Contracts\Cache\Store;
use League\Flysystem\Filesystem;
use Throwable;

/**
 * File Cache using Azure blob storage, this cache adapter does not implement auto delete of files which should be 
 * implemented by the blob storage
 */
class AzureCacheAdapter implements Store
{

    private Filesystem $fileSystem;
    private string $rootDirectory = '';

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    private function getPath(string $key)
    {
        $hash = sha1($key);
        return $this->rootDirectory . '/' . $hash;
    }

    public function get($key)
    {
        $path = $this->getPath($key);
        try {
            return $this->fileSystem->read($path);
        } catch (Throwable $e) {
            throw new GaelONotFoundException("File doesn't exist in azure cache");
        }
    }

    public function many(array $keys)
    {
        $answer = [];
        foreach ($keys as $key) {
            $answer[$key] = $this->get($key);
        }
        return $answer;
    }

    public function put($key, $value, $seconds)
    {
        $path = $this->getPath($key);

        $this->fileSystem->write($path, $value);
        return true;
    }

    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $seconds);
        }
        return true;
    }

    public function increment($key, $value = 1)
    {
        throw new GaelOException('No implementation for increment in Azure Cache');
    }

    public function decrement($key, $value = 1)
    {
        throw new GaelOException('No implementation for decrement in Azure Cache');
    }

    public function forever($key, $value)
    {
        $this->put($key, $value, 0);
        return true;
    }

    public function forget($key)
    {
        $path = $this->getPath($key);
        try {
            $this->fileSystem->delete($path);
            return true;
        } catch (Throwable $t) {
            throw new GaelONotFoundException("File cache delete file");
        }
    }

    public function flush()
    {
        $files = $this->fileSystem->listContents($this->rootDirectory);
        foreach ($files as $file) {
            $this->fileSystem->delete($file->path());
        }
        return true;
    }

    public function getPrefix()
    {
        return '';
    }
}
