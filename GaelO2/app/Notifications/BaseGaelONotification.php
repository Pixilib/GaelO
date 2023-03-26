<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BaseGaelONotification extends Notification
{

    protected string $object;
    protected string $message;

    public function __construct(string $object, string $message)
    {
        $this->object = $object;
        $this->message = $message;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

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
