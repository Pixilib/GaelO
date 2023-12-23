<?php

namespace App\GaelO\UseCases\CreateDocumentation;

/**
 * @codeCoverageIgnore
 */
class CreateDocumentationRequest
{
    public int $currentUserId;
    public string $name;
    public string $studyName;
    public string $version;
    public ?bool $investigator = false;
    public ?bool $controller = false;
    public ?bool $monitor = false;
    public ?bool $reviewer = false;
}
