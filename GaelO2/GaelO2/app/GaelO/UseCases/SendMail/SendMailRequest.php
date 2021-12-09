<?php

namespace App\GaelO\UseCases\SendMail;

class SendMailRequest{
    public string $currentUserId;
    public string $study;
    public string $subject;
    public string $content;
    public string $role;
    public ?int $userId = null;
    public ?string $patientId = null;
    public ?int $visitId = null;
    public ?bool $toAdministrators = false;
}
