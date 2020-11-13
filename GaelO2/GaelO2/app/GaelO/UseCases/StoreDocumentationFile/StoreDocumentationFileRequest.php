<?php

namespace App\GaelO\UseCases\StoreDocumentationFile;

class StoreDocumentationFileRequest{
    public int $currentUserId;
    public int $id;
    public string $binaryData;
}
