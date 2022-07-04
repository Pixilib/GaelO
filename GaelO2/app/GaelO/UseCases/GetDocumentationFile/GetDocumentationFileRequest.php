<?php

namespace App\GaelO\UseCases\GetDocumentationFile;

class GetDocumentationFileRequest
{
    public int $currentUserId;
    public int $id;
    public string $role;
}
