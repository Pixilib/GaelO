<?php

namespace App\GaelO\UseCases\Login;

class LoginResponse {
    public ?array $body = null;
    public int $status;
    public string $statusText;
}
