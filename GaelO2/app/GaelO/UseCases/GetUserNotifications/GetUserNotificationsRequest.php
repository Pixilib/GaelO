<?php

namespace App\GaelO\UseCases\GetUserNotifications;

class GetUserNotificationsRequest {
    public int $currentUserId;
    public int $userId;
    public bool $onlyUnread;
}