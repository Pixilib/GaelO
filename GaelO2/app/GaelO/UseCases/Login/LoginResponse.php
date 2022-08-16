<?php

namespace App\GaelO\UseCases\Login;

class LoginResponse
{
    public $body = null;
    public ?bool $onboarded = null;
    public int $status;
    public string $statusText;
}
