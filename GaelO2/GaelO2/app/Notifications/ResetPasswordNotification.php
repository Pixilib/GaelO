<?php

namespace App\Notifications;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\SettingsConstants;
use App\Mail\ResetPassword;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification
{

    private string $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $resetUrl = url('password/reset', $this->token);

        $platformName = FrameworkAdapter::getConfig(SettingsConstants::PLATFORM_NAME);
        $webAddress = FrameworkAdapter::getConfig(SettingsConstants::APP_URL);
        $corporation = FrameworkAdapter::getConfig(SettingsConstants::CORPORATION);
        $adminEmail = FrameworkAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);

        return ( new ResetPassword(
            ['url' => $resetUrl,
            'platformName'=> $platformName,
            'corporation'=> $corporation,
            'webAddress'=>$webAddress,
            'adminEmail'=> $adminEmail,
            'name'=>"user"]
            )
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
