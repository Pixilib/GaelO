<?php

namespace App\GaelO\UseCases\GetDicomsStudiesFromStudy;

class GetDicomsStudiesFromStudyRequest
{
    public int $currentUserId;
    public string $studyName;
    public bool $withTrashedStudies = false;
    public bool $withTrashedSeries = false;
}
