<?php
namespace App\GaelO\UseCases\CreateRole;

class CreateRoleRequest {
    public int $currentUserId;
    public int $userId;
    public string $study;
    public string $role;
}
