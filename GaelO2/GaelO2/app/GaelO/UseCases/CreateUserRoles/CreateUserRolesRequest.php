<?php
namespace App\GaelO\UseCases\CreateUserRoles;

class CreateUserRolesRequest {
    public int $currentUserId;
    public int $userId;
    public string $study;
    public string $role;
}
