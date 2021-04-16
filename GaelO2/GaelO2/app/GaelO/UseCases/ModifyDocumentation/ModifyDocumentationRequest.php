<?php

namespace App\GaelO\UseCases\ModifyDocumentation;

class ModifyDocumentationRequest{
    public int $currentUserId;
    public int $id;
    public string $version;
    public bool $investigator;
    public bool $controller;
    public bool $monitor;
    public bool $reviewer;
}
