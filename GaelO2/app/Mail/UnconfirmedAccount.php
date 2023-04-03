<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnconfirmedAccount extends Mailable implements ShouldQueue
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
            subject: "GaelO - Account Reactivation"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_unconfirmed_account',
            with: $this->parameters
        );
    }

}
