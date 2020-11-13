<?php

namespace App\GaelO\UseCases\CreateDocumentation;

class CreateDocumentationRequest{
    public int $currentUserId;
    public string $name;
    public string $studyName;
    public string $vesion;
    public bool $investigator;
    public bool $controller;
    public bool $monitor;
    public bool $reviewer;
}
