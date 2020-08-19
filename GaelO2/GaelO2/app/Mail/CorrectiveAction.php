<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectiveAction extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $parameters)
    {
        $this->parameters =$parameters;
        /*
        array('done'=>'',
        'study'=> '',
        'patientCode'=>'',
        'visitType'=>'')
        */
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_corrective_action')
        ->object($this->parameters['study']." - Corrective Action Patient - ".$this->parameters['patientCode'])
        ->with($this->parameters);
    }
}
