<?php

namespace App\GaelO\Services;

use App\GaelO\Adapters\FileCacheAdapter;

class FileCacheService
{
    private FileCacheAdapter $fileCacheAdapter;

    public function __construct(FileCacheAdapter $fileCacheAdapter)
    {
        $this->fileCacheAdapter = $fileCacheAdapter;
    }

    public function getSeriesPreview(string $seriesInstanceUID, int $index)
    {
        return $this->fileCacheAdapter->get('preview-' . $seriesInstanceUID . '-' . $index);
    }

    public function storeSeriesPreview(string $seriesInstanceUID, int $index, $value)
    {
        return $this->fileCacheAdapter->store('preview-' . $seriesInstanceUID . '-' . $index, $value);
    }

    public function deleteSeriesPreview(string $seriesInstanceUID, int $index)
    {
        return $this->fileCacheAdapter->delete('preview-' . $seriesInstanceUID . '-' . $index);
    }

    public function storeDicomMetadata(string $uid, $value)
    {
        return $this->fileCacheAdapter->store('metadata-' . $uid, $value);
    }

    public function getDicomMetadata(string $uid)
    {
        return $this->fileCacheAdapter->get('metadata-' . $uid);
    }

    public function deleteDicomMetadata(string $uid)
    {
        return $this->fileCacheAdapter->delete('metadata-' . $uid);
    }
}
