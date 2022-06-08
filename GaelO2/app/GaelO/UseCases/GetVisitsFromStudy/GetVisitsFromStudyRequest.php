<?php

namespace App\GaelO\UseCases\GetVisitsFromStudy;

class GetVisitsFromStudyRequest
{
    public string $studyName;
    public ?int $visitTypeId = null;
    public int $currentUserId;
}
