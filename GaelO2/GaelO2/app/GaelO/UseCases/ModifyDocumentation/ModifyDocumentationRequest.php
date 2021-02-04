<?php

namespace App\GaelO\UseCases\ModifyDocumentation;

class ModifyDocumentationRequest{
    public int $currentUserId;
    public int $id;
    public string $studyName;
    public ?string $documentDate = null;
    public ?string $version = null;
    public ?bool $investigator = false;
    public ?bool $controller = false;
    public ?bool $monitor = false;
    public ?bool $reviewer = false;
}
