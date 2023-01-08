<?php

namespace App\GaelO\UseCases\CreateFileToForm;

class CreateFileToFormRequest
{
    public int $currentUserId;
    public int $id;
    public string $key;
    public string $contentType;
    public string $binaryData;
    public ?string $extension = null;
}
