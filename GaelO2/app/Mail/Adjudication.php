<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Adjudication extends Mailable implements ShouldQueue
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
            'study'=>'',
            'patientId'=>'',
            'visitType'=>'',
            'visitId' => ''
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
        return $this->view('mails.mail_adjudication')
        ->subject($this->parameters['study']." - Awaiting Adjudication Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'] )
        ->with($this->parameters);
    }
}
