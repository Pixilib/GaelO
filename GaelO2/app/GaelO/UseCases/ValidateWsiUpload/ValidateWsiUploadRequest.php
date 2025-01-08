<?php

namespace App\GaelO\UseCases\ValidateWsiUpload;

class ValidateWsiUploadRequest
{
    public int $currentUserId;
    public int $visitId;
    public array $uploadedFileTusId;

}
