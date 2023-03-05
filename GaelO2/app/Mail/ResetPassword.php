<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

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

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "GaelO - Reset Password"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_reset_password',
            with: $this->parameters
        );
    }

}
