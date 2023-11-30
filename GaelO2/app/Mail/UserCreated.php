<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCreated extends Mailable implements ShouldQueue
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
            subject: 'GaelO - User Created'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_create_user',
            with: $this->parameters
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', '/gaelo-overview.pdf')->as('gaelo-overview.pdf')->withMime('application/pdf'),
            Attachment::fromStorageDisk('public', '/gaelo-deidentification.pdf')->as('gaelo-deidentification.pdf')->withMime('application/pdf')
        ];
    }
}
