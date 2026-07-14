<?php

namespace App\Mail;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailUserSync extends Mailable
{
    use SerializesModels;

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

    public function attachments(): array
    {
        $attachementPaths = $this->parameters['attachements'] ?? [];
        return [
            ...array_map(function($path) {return Attachment::fromPath($path); }, $attachementPaths)
        ];
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_user',
            with: $this->parameters
        );
    }
}
