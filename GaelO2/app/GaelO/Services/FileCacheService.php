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

    /**
     * Methodology should be 41 / 25 / 4
     */
    public function storeTmtvPreview(string $seriesInstanceUID, string $methodology, $value, ?int $ttl = null)
    {
        return $this->fileCacheAdapter->store('tmtv-preview-' . $seriesInstanceUID . '-' . $methodology, $value, $ttl);
    }

    public function storeTmtvResults(string $seriesInstanceUID, string $methodology, $value, ?int $ttl = null)
    {
        return $this->fileCacheAdapter->store('tmtv-results-' . $seriesInstanceUID . '-' . $methodology, $value, $ttl);
    }

    public function deleteSeriesPreview(string $seriesInstanceUID, int $index)
    {
        return $this->fileCacheAdapter->delete('tmtv-preview-' . $seriesInstanceUID . '-' . $index);
    }

    public function deleteTmtvResults(string $seriesInstanceUID, int $index)
    {
        return $this->fileCacheAdapter->delete('tmtv-results-' . $seriesInstanceUID . '-' . $index);
    }

    public function deleteTmtvPreview(string $seriesInstanceUID, string $methodology)
    {
        return $this->fileCacheAdapter->delete('tmtv-' . $seriesInstanceUID . '-' . $methodology);
    }

    public function getTmtvResults(string $seriesInstanceUID, string $methodology)
    {
        return $this->fileCacheAdapter->get('tmtv-results-' . $seriesInstanceUID . '-' . $methodology);
    }

    public function getTmtvPreview(string $seriesInstanceUID, string $methodology)
    {
        return $this->fileCacheAdapter->get('tmtv-preview-' . $seriesInstanceUID . '-' . $methodology);
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
