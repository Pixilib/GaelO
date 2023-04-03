<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailUser extends Mailable implements ShouldQueue
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
        $subject = $this->parameters['study'] ?
            $this->parameters['study'] . " - " . $this->parameters['subject'] :
            $this->parameters['subject'];

        return new Envelope(
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_user',
            with: $this->parameters
        );
    }
}
