<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_create_user')->with([
            'name' => 'salim',
            'username' => 'salim',
            'password' => 'salim',
            'platformName'=>'GaelO',
            'webAddress' => 'gaelo.fr',
            'corporation' => 'lysarc',
            'adminEmail' => 'salim.kanoun@gmail.com'
        ]);;
    }
}
