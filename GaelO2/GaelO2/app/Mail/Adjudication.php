<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Adjudication extends Mailable
{
    use Queueable, SerializesModels;

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
            'study'=>'',
            'patientCode'=>'',
            'visitType'=>''
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
        return $this->view('mails.adjudication')
        ->object($this->parameters['study']." - Awaiting Adjudication Patient - ".$this->parameters['patientCode'])
        ->with($this->parameters);
    }
}
