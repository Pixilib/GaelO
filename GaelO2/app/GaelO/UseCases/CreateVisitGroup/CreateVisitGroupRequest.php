<?php

namespace App\GaelO\UseCases\CreateVisitGroup;

class CreateVisitGroupRequest {
    public int $currentUserId;
    public string $currentVisitId;
    public string $studyName;
    public string $name;
    public string $modality;
}
