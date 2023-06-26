<?php

namespace App\GaelO\UseCases\GetNiftiFileSupervisor;

class GetNiftiFileSupervisorRequest
{
    public int $currentUserId;
    public string $studyName;
    public string $seriesInstanceUID;
    public bool $compress;
}
