<?php

namespace App\GaelO\UseCases\DeleteUserNotifications;

class DeleteUserNotificationsRequest
{
    public int $currentUserId;
    public int $userId;
    public array $notificationsIds;
}
