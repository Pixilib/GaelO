<?php

namespace App\GaelO\UseCases\GetDocumentationFile;

class GetDocumentationFileResponse
{
    public $body = null;
    public string $filePath;
    public string $filename;
    public int $status;
    public string $statusText;
}
