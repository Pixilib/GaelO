<?php

namespace App\GaelO\UseCases\ModifyQualityControlCorrectiveAction;

class ModifyQualityControlCorrectiveActionRequest{
    public int $currentUserId;
    public int $visitId;
    public bool $newSeriesUploaded;
    public bool $newInvestigatorForm;
    public bool $formCorrected;
    public bool $correctiveActionDone;
    public ?string $comment;
}
