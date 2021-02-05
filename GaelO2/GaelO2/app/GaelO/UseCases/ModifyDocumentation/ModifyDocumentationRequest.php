<?php

namespace App\GaelO\UseCases\ModifyDocumentation;

class ModifyDocumentationRequest{
    public int $currentUserId;
    public int $id;
    public string $studyName;
    public ?string $documentDate = null;
    public ?string $version = null;
    public ?bool $investigator = null;
    public ?bool $controller = null;
    public ?bool $monitor = null;
    public ?bool $reviewer = null;
}
