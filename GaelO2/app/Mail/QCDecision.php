<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QCDecision extends Mailable implements ShouldQueue
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
            "controlDecision"=> "",
            "study" => "",
            "patientId" => "",
            "visitType" => "",
            "visitId"=>"",
            "visitModality"=>"",
            "formDecision" => "",
            "formComment" => "",
            "imageDecision" => "",
            "imageComment" => ""

        )
        */
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->parameters['study'] . " - Quality Control Patient - " . $this->parameters['patientCode'] . " - Visit - " . $this->parameters['visitType']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.mail_qc_decision',
            with: $this->parameters
        );
    }
}
