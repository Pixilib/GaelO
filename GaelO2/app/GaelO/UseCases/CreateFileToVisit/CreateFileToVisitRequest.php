<?php

namespace App\GaelO\UseCases\CreateFileToVisit;

class CreateFileToVisitRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $key;
    public string $contentType;
    public string $binaryData;
}
