<?php

namespace App\GaelO\UseCases\GetRolesInStudyFromUser;

class GetRolesInStudyFromUserRequest
{
    public string $studyName;
    public int $currentUserId;
    public int $userId;
}
