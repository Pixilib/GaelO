<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangePasswordDeactivated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
            subject: "GaelO - Blocked Account"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_change_password_deactivated',
            with: $this->parameters
        );
    }
    
}
