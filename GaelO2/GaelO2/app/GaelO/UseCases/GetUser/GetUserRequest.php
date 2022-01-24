<?php

namespace App\GaelO\UseCases\GetUser;

class GetUserRequest {
    public int $currentUserId;
    public ?int $id ;
    public bool $withTrashed;
}
