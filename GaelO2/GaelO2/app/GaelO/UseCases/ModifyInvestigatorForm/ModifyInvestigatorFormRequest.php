<?php

namespace App\GaelO\UseCases\ModifyInvestigatorForm;

class ModifyInvestigatorFormRequest{
    public int $currentUserId;
    public int $visitId;
    public array $data;
    public bool $validated;
}
