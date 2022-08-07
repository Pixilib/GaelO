<?php

namespace App\GaelO\UseCases\ModifyValidatedDocumentationForRole;

class ModifyValidatedDocumentationForRoleRequest
{
    public int $currentUserId;
    public int $userId;
    public string $studyName;
    public string $role;
    public string $version;
}
