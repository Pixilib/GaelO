<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QcReport extends Mailable //implements ShouldQueue
{
    //use Queueable, SerializesModels;

    protected array $parameters;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_qc_report')
        ->subject($this->parameters['study']." - Qc Report Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'])
        ->with($this->parameters);
    }
}
