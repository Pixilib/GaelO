<?php

namespace App\GaelO\UseCases\GetUserRoles;

class GetUserRolesRequest {
    public int $currentUserId;
    public int $userId;
    public string $study;
}
