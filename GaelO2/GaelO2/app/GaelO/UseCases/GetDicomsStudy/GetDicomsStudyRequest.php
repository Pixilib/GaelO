<?php

namespace App\GaelO\UseCases\GetDicomsStudy;

class GetDicomsStudyRequest {
    public int $currentUserId;
    public string $studyName;
    public bool $expand = false;
}
