<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

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

        return (new MailMessage)->view('mails.mail_create_user', ['url' => $verificationUrl, 'platformName'=> 'test', 'corporation'=> 'd', 'webAddress'=>'a', 'adminEmail'=> '', 'name'=>"GaelO"]);

    }

}
