<?php

namespace App\Notifications;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Constants\SettingsConstants;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;

class VerifyEmailNotification extends VerifyEmail 
{

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $verificationUrl = $this->verificationUrl($notifiable);

        $platformName = FrameworkAdapter::getConfig(SettingsConstants::PLATFORM_NAME);
        $webAddress = FrameworkAdapter::getConfig(SettingsConstants::APP_URL);
        $corporation = FrameworkAdapter::getConfig(SettingsConstants::CORPORATION);
        $adminEmail = FrameworkAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);

        return (new MailMessage)->subject('GaelO - User Created')
            ->view('mails.mail_create_user', ['url' => $verificationUrl,
            'platformName'=> $platformName,
            'corporation'=> $corporation,
            'webAddress'=>$webAddress,
            'adminEmail'=> $adminEmail,
            'name'=>"user"]);
    }

}
