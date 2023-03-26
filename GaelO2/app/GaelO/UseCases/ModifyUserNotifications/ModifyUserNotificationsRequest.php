<?php

namespace App\GaelO\UseCases\ModifyUserNotifications;

class ModifyUserNotificationsRequest
{
    public int $currentUserId;
    public int $userId;
    public array $notificationsIds;
}
