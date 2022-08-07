<?php

namespace App\GaelO\UseCases\GetUserRoleByName;

class GetUserRoleByNameRequest
{
    public int $userId;
    public int $currentUserId;
    public string $studyName;
    public string $role;
}
