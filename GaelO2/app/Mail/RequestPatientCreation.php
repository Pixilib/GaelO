<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestPatientCreation extends Mailable implements ShouldQueue
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
        $subject = $this->parameters['study'] . " - Patient Creation Request";

        return new Envelope(
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_request_patient_creation',
            with: $this->parameters
        );
    }
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => json_encode($this->parameters['patients'], JSON_PRETTY_PRINT), 'patients.json')->withMime('application/json')
        ];
    }
}
