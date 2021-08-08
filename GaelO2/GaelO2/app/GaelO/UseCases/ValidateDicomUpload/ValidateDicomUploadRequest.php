<?php

namespace App\GaelO\UseCases\ValidateDicomUpload;

class ValidateDicomUploadRequest{
    public int $currentUserId;
    public int $visitId;
    public string $originalOrthancId;
    public array $uploadedFileTusId;
    public int $numberOfInstances;
}
