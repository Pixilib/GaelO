<?php

namespace App\GaelO\UseCases\ReactivateStudy;

class ReactivateStudyRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $reason;
}
