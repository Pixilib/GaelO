<?php

namespace App\GaelO\UseCases\Reminder;

class ReminderRequest{
    public string $currentUserId;
    public string $study;
    public string $subject;
    public string $content;
    public string $role;
    public ?int $centerCode = null;

}
