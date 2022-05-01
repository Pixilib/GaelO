<?php

namespace App\GaelO\UseCases\FindUser;

class FindUserRequest {
    public int $currentUserId;
    public string $email;
    public string $studyName;
}
