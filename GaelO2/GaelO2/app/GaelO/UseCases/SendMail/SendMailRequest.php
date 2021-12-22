<?php

namespace App\GaelO\UseCases\SendMail;

class SendMailRequest{
    public string $currentUserId;
    public string $study;
    public string $subject;
    public string $content;
    public string $role;
    public $userIds;
    public ?string $patientId = null;
    public ?int $visitId = null;
    public ?bool $toAdministrators = false;
    public $patients = null;
}
