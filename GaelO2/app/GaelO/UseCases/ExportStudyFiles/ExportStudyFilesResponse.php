<?php

namespace App\GaelO\UseCases\ExportStudyFiles;

use Psr\Http\Message\StreamInterface;

class ExportStudyFilesResponse
{
    public int $status;
    public string $statusText;
    public StreamInterface $zipFile;
    public string $fileName;
}
