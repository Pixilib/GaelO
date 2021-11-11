<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorrectiveAction extends Mailable implements ShouldQueue
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
        $this->parameters =$parameters;
        /*
        array('done'=>'',
        'study'=> '',
        'patientId'=>'',
        'visitId'=>'',
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
        ->subject($this->parameters['study']." - Corrective Action Patient - ".$this->parameters['patientId'])
        ->with($this->parameters);
    }
}
