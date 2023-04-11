<?php

namespace App\GaelO\UseCases\CreatePatientTags;

class CreatePatientTagsRequest
{
    public int $currentUserId;
    public string $patientId;
    public string $tag;
    public string $studyName;
}
