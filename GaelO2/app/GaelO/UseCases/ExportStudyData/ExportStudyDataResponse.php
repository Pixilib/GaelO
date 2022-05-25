<?php

namespace App\GaelO\UseCases\ExportStudyData;

class ExportStudyDataResponse
{
    public int $status;
    public string $statusText;
    public string $zipFile;
    public string $fileName;
}
