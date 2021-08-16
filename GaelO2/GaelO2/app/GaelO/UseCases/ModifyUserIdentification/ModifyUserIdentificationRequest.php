<?php

namespace App\GaelO\UseCases\ModifyUserIdentification;

class ModifyUserIdentificationRequest {
    public int $currentUserId;
    public int $userId;
    public ?string $lastname = null;
    public ?string $firstname = null;
    public ?string $phone = null;
    public string $username;
    public string $email;
}
