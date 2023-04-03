<?php

namespace App\Notifications;

class AutoImportPatients extends BaseGaelONotification
{

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'object' => $this->object,
            'message' => $this->message
        ];
    }

}
