<?php

namespace App\GaelO\UseCases\CreateDocumentationFile;

class CreateDocumentationFileRequest{
    public int $currentUserId;
    public int $id;
    public string $binaryData;
    public string $contentType;
}
