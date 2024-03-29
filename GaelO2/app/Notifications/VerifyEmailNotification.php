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
        $mailFromAddress = FrameworkAdapter::getConfig(SettingsConstants::MAIL_FROM_ADDRESS);
        $mailReplyTo = FrameworkAdapter::getConfig(SettingsConstants::MAIL_REPLY_TO_DEFAULT);

        return (new MailMessage)->subject('GaelO - Verify Email')
            ->view('mails.mail_verify_email', ['url' => $verificationUrl,
            'platformName'=> $platformName,
            'corporation'=> $corporation,
            'webAddress'=>$webAddress,
            'mailFromAddress'=> $mailFromAddress,
            'mailReplyTo' => $mailReplyTo,
            'name'=>"user"]);
    }

}
