<?php

namespace App\GaelO\UseCases\ModifyQualityControl;

class ModifyQualityControlRequest
{
    public int $currentUserId;
    public int $visitId;
    public string $studyName;
    public string $stateQc;
    public ?bool $imageQc = null;
    public ?bool $formQc = null;
    public ?string $imageQcComment = null;
    public ?string $formQcComment = null;
}
