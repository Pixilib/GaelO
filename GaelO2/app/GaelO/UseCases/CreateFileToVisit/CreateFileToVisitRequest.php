<?php

namespace App\GaelO\UseCases\CreateFileToVisit;

class CreateFileToVisitRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public string $key;
    public ?string $contentType = null;
    public string $content;
    public ?string $extension = null;
}
