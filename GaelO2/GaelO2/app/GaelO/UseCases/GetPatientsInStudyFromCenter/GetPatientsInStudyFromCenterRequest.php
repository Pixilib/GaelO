<?php

namespace App\GaelO\UseCases\GetPatientsInStudyFromCenter;

class GetPatientsInStudyFromCenterRequest {
    public int $currentUserId;
    public string $role;
    public string $studyName;
    public int $centerCode;
}
