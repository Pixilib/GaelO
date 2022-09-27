<?php

namespace App\GaelO\UseCases\SendMail;

class SendMailRequest
{
    public string $currentUserId;
    public ?string $studyName = null;
    public string $role;
    public string $subject;
    public string $content;
    public $userIds = null;
    public ?string $patientId = null;
    public ?int $visitId = null;
    public ?bool $toAdministrators = false;
}
