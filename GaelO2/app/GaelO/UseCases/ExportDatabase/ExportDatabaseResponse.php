<?php

namespace App\GaelO\UseCases\ExportDatabase;

class ExportDatabaseResponse
{
    public int $status;
    public string $statusText;
    public string $zipFile;
    public string $fileName;
}
