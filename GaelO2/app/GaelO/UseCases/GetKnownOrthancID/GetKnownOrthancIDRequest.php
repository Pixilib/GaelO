<?php

namespace App\GaelO\UseCases\GetKnownOrthancID;

class GetKnownOrthancIDRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $orthancStudyID;
}
