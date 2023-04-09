<?php

namespace App\GaelO\UseCases\DeletePatientTags;

class DeletePatientTagsRequest
{
    public int $currentUserId;
    public string $patientId;
    public string $tag;
    public string $studyName;
}
