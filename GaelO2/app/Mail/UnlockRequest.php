<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnlockRequest extends Mailable implements ShouldQueue
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
        array(
            'role'=>'',
            'visitType'=>'',
            'patientNum'=>'',
            'visitId'=>'',
            'study'=>'',
            'reason'=>''
        );
        */
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->parameters['study'] . " - Unlock Request - Patient - " . $this->parameters['patientCode'] . " - Visit - " . $this->parameters['visitType']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_unlock_request',
            with: $this->parameters
        );
    }
}
