<?php

namespace App\GaelO\UseCases\ChangePassword;

class ChangePasswordRequest {
    public string $previous_password;
    public string $password1;
    public string $password2;
}
