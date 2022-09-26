<?php

namespace App\GaelO\UseCases\RequestPatientCreation;

class RequestPatientCreationRequest
{
    public string $currentUserId;
    public string $role;
    public string $studyName;
    public string $content;
    public array $patients;
}
