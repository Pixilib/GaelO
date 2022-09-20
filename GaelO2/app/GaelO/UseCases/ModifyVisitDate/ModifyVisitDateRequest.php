<?php

namespace App\GaelO\UseCases\ModifyVisitDate;

class ModifyVisitDateRequest
{
    public int $currentUserId;
    public string $studyName;
    public int $visitId;
    public string $visitDate;
}
