<?php

namespace App\GaelO\UseCases\GetInvestigatorFormsFromVisitType;

class GetInvestigatorFormsFromVisitTypeRequest{
    public int $currentUserId;
    public int $visitTypeId;
    public string $studyName;
}
