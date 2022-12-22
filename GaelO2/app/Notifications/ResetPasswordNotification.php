<?php

namespace App\Notifications;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\SettingsConstants;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $resetUrl = url('api/tools/reset-password', $this->token);
        $platformName = FrameworkAdapter::getConfig(SettingsConstants::PLATFORM_NAME);
        $webAddress = FrameworkAdapter::getConfig(SettingsConstants::APP_URL);
        $corporation = FrameworkAdapter::getConfig(SettingsConstants::CORPORATION);
        $mailFromAddress = FrameworkAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);
        $mailReplyTo = FrameworkAdapter::getConfig(SettingsConstants::MAIL_REPLY_TO_DEFAULT);

        return (new MailMessage)
            ->subject('GaelO - Set New Password')
            ->view('mails.mail_reset_password', ['url' => $resetUrl,
            'platformName'=> $platformName,
            'corporation'=> $corporation,
            'webAddress'=>$webAddress,
            'mailFromAddress'=> $mailFromAddress,
            'mailReplyTo' => $mailReplyTo,
            'name'=>'User']);
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
