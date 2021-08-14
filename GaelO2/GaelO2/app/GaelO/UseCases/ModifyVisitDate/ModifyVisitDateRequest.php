<?php

namespace App\GaelO\UseCases\ModifyVisitDate;

class ModifyVisitDateRequest {
    public int $currentUserId;
    public int $visitId;
    public string $visitDate;
}
