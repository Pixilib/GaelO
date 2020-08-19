<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UploadFailure extends Mailable
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
        array('idVisit' =>'',
        'patientCode' => '',
        'visitType'=> '',
        'study'=>'',
        'zipPath'=>'',
        'username'=>'',
        'errorMessage'=>'');
        */
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.mail_upload_failure')
            ->subject($this->parameters['study']." - Error During Import Patient - ".$this->parameters['patientCode'])
            ->with($this->parameters);
    }
}
