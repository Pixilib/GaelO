<?php

namespace App\GaelO\UseCases\ModifyQualityControlReset;

class ModifyQualityControlResetRequest
{
    public int $visitId;
    public string $studyName;
    public int $currentUserId;
    public string $reason;
}
