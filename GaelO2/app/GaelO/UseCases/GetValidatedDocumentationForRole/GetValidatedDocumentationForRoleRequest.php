<?php

namespace App\GaelO\UseCases\GetValidatedDocumentationForRole;

class GetValidatedDocumentationForRoleRequest
{
    public int $userId;
    public int $currentUserId;
    public string $studyName;
    public string $role;
}
