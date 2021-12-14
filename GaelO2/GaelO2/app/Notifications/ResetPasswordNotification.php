<?php

namespace App\Notifications;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\SettingsConstants;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{

    private string $token;
    private array $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, array $user)
    {
        $this->token = $token;
        $this->user = $user;
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
        //If user password is set, mail is meant to reset password 
        if($this->user['password'] !== null) $template = 'mails.mail_reset_password';
        //If not, mail is meant to set password upon user creation
        else $template = 'mails.mail_create_user';
        $platformName = FrameworkAdapter::getConfig(SettingsConstants::PLATFORM_NAME);
        $webAddress = FrameworkAdapter::getConfig(SettingsConstants::APP_URL);
        $corporation = FrameworkAdapter::getConfig(SettingsConstants::CORPORATION);
        $adminEmail = FrameworkAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);

        return (new MailMessage)
            ->view('mails.mail_reset_password', ['url' => $resetUrl,
            'platformName'=> $platformName,
            'corporation'=> $corporation,
            'webAddress'=>$webAddress,
            'adminEmail'=> $adminEmail,
            'name'=>$this->user['name']]);
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
