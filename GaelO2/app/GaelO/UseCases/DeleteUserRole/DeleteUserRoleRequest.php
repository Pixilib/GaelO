<?php

namespace App\GaelO\UseCases\DeleteUserRole;

class DeleteUserRoleRequest
{
    public int $userId;
    public String $studyName;
    public String $role;
    public int $currentUserId;
}
