<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnlockRequest extends Mailable implements ShouldQueue
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
            'role'=>'',
            'visitType'=>'',
            'patientNum'=>'',
            'visitId'=>'',
            'study'=>'',
            'reason'=>''
        );
        */
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_unlock_request')
            ->subject($this->parameters['study']." - Unlock Request - Patient - ".$this->parameters['patientId']." - Visit - ".$this->parameters['visitType'])
            ->with($this->parameters);
    }
}
