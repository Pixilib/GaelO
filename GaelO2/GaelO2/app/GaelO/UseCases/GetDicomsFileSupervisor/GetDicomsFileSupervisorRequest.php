<?php

namespace App\GaelO\UseCases\GetDicomsFileSupervisor;

class GetDicomsFileSupervisorRequest {
    public int $currentUserId;
    public array $seriesInstanceUID;
    public string $studyName;
}
