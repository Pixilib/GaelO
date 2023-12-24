<?php

namespace App\GaelO\UseCases\CreateFileToForm;

/**
 * @codeCoverageIgnore
 */
class CreateFileToFormRequest
{
    public int $currentUserId;
    public int $id;
    public string $key;
    public string $contentType;
    public string $binaryData;
}
