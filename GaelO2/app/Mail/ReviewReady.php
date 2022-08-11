<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReviewReady extends Mailable implements ShouldQueue
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
        array('study'=>'',
        'visitId=>'',
        'patientId'=>'',
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
        return $this->view('mails.mail_review_ready')
        ->subject($this->parameters['study']." - Awaiting Review Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'])
        ->with($this->parameters);
    }
}
