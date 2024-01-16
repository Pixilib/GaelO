<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\FileCacheAdapter;
use App\GaelO\Exceptions\GaelONotFoundException;

class FileCacheService
{
    private FileCacheAdapter $fileCacheAdapter;

    public function __construct(FileCacheAdapter $fileCacheAdapter)
    {
        $this->fileCacheAdapter = $fileCacheAdapter;
    }

    public function getSeriesPreview(string $seriesInstanceUID, int $index)
    {
        $data = $this->fileCacheAdapter->get('preview-' . $seriesInstanceUID . '-' . $index);
        if ($data == null) {
            throw new GaelONotFoundException();
        }
        return $data;
    }

    /**
     * ttl in seconds may not be supported with all drivers (ok with redis not with Azure)
     */
    public function storeSeriesPreview(string $seriesInstanceUID, int $index, $value, ?int $ttl = null)
    {
        return $this->fileCacheAdapter->store('preview-' . $seriesInstanceUID . '-' . $index, $value, $ttl);
    }

    public function deleteSeriesPreview(string $seriesInstanceUID, int $index)
    {
        return $this->fileCacheAdapter->delete('preview-' . $seriesInstanceUID . '-' . $index);
    }

    /**
     * ttl in seconds may not be supported with all drivers (ok with redis not with Azure)
     */
    public function storeDicomMetadata(string $uid, $value, ?int $ttl = null)
    {
        return $this->fileCacheAdapter->store('metadata-' . $uid, $value, $ttl);
    }

    public function getDicomMetadata(string $uid)
    {
        $data = $this->fileCacheAdapter->get('metadata-' . $uid);
        if ($data == null) {
            throw new GaelONotFoundException();
        }
        return $data;
    }

    public function deleteDicomMetadata(string $uid)
    {
        return $this->fileCacheAdapter->delete('metadata-' . $uid);
    }
}
