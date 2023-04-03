<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UploadFailure extends Mailable implements ShouldQueue
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
        array('idVisit' =>'',
        'patientId' => '',
        'visitType'=> '',
        'study'=>'',
        'zipPath'=>'',
        'userId'=>'',
        'errorMessage'=>'');
        */
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->parameters['study']." - Error During Import Patient - ".$this->parameters['patientId']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_upload_failure',
            with: $this->parameters
        );
    }

}
