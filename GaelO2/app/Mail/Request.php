<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Request extends Mailable implements ShouldQueue
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
        array('name'=>'',
        'email'=>'',
        'center'=>'',
        'request'=>'')
        */
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "GaelO - Request"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_request',
            with: $this->parameters
        );
    }
}
