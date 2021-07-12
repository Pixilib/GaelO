<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsMetadataFromVisitType;

class GetInvestigatorFormsMetadataFromVisitTypeRequest
{
    public int $currentUserId;
    public int $visitTypeId;
    public string $studyName;
}
