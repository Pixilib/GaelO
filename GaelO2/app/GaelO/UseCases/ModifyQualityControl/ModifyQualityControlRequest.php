<?php

namespace App\GaelO\UseCases\ModifyQualityControl;

class ModifyQualityControlRequest {
    public int $currentUserId;
    public int $visitId;
    public string $stateQc;
    //public int $controllerId; ?
    public bool $imageQc;
    public bool $formQc;
    public ?string $imageQcComment = null;
    public ?string $formQcComment = null;
}
