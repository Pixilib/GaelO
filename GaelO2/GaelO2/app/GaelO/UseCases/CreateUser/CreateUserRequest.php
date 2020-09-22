<?php

namespace App\GaelO\UseCases\CreateUser;

class CreateUserRequest {
    public int $currentUserId;
    public string $lastname;
    public string $firstname;
    public string $username;
    public string $email;
    public ?string $phone = null;
    public bool $administrator;
    public int $centerCode;
    public string $job;
    public ?string $orthancAddress = null;
    public ?string $orthancLogin = null;
    public ?string $orthancPassword = null;
}
