<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

class GetDicomsFileSupervisorRequest {
    public string $studyName;
    public int $currentUserId;
    public array $seriesInstanceUID;
}
