<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLoged extends Mailable implements ShouldQueue
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

    public function envelope(): Envelope{
        return new Envelope(
            subject: "GaelO - Admin Logged In"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_admin_logged',
            with: $this->parameters
        );
    }

}
