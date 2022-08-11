<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnlockQcRequest extends Mailable implements ShouldQueue
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
            'name'=>'',
            'PatientId'=>'',
            'visitType'=>'',
            'visitId'=>'',
            'study'=>'',
            'patientId'=>'',
            'messages'=>''
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
        return $this->view('mails.mail_unlock_qc_request')
            ->subject($this->parameters['study']." - Unlock QC Request - Patient - ".$this->parameters['patientCode']." - Visit - ".$this->parameters['visitType'])
            ->with($this->parameters);
    }
}
