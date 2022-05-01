<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ResetPassword extends Mailable
{

    protected array $parameters;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('GaelO - Reset Password')
            ->view('mails.mail_reset_password')
            ->with($this->parameters);
    }
}
