<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
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

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_qc_decision')
        ->subject($this->parameters['study']." - Quality Control Patient - ".$this->parameters['patientId']." - Visit - ".$this->parameters['visitType'])
        ->with($this->parameters);
    }
}
