<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QCDecision extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
        /*
        array(
            "controlDecision"=> "",
            "study" => "",
            "patientCode" => "",
            "visitType" => "",
            "formDecision" => "",
            "formComment" => "",
            "imageDecision" => "",
            "imageComment" => ""

        )
        */

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail.qc_decision')
        ->object($this->parameters['study']." - Quality Control")
        ->with($this->parameters);
    }
}
