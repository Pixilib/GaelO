<?php

namespace App\GaelO\UseCases\SendReminder;

class SendReminderRequest{
    public string $currentUserId;
    public string $studyName;
    public string $subject;
    public string $content;
    public string $role;
    public ?int $centerCode = null;

}
