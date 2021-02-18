<?php

namespace App\GaelO\UseCases\CreateInvestigatorForm;

class CreateInvestigatorFormRequest{
    public int $currentUserId;
    public int $visitId;
    public array $data;
    public bool $validated;
}
