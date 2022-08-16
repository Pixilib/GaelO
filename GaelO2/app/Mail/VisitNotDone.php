<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VisitNotDone extends Mailable implements ShouldQueue
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
        array('patientId'=>'',
        'study'=>'',
        'visitType'=>'',
        'visitId'=>'',
        'creatorUser'=>'');
        */
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_visit_not_done')
            ->subject($this->parameters['study']." - Visit Not Done - Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'])
            ->with($this->parameters);
    }
}
