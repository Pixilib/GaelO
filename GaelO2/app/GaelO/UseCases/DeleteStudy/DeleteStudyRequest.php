<?php

namespace App\GaelO\UseCases\DeleteStudy;

class DeleteStudyRequest
{
    public String $studyName;
    public int $currentUserId;
    public string $reason;
}
