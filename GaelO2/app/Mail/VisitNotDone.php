<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitNotDone extends Mailable implements ShouldQueue
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
        /*
        array('patientId'=>'',
        'study'=>'',
        'visitType'=>'',
        'visitId'=>'',
        'creatorUser'=>'');
        */
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->parameters['study'] . " - Visit Not Done - Patient - " . $this->parameters['patientCode'] . " - Visit - " . $this->parameters['visitType']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_visit_not_done',
            with: $this->parameters
        );
    }
}
