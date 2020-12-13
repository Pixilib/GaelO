<?php

namespace App\GaelO\UseCases\ReactivateDicomStudy;

class ReactivateDicomStudyRequest{
    public int $currentUserId;
    public string $studyInstanceUID;
}
