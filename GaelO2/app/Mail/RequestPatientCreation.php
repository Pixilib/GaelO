<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestPatientCreation extends Mailable implements ShouldQueue
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
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //If mail is from admin, there is no study context
        $subject = $this->parameters['study'] . " - Patient Creation Request";

        $mail = $this->view('mails.mail_request_patient_creation')
            ->subject($subject)
            ->with($this->parameters);

        //Attach JSON of Patient request creation
        $mail->attachData(json_encode($this->parameters['patients'], JSON_PRETTY_PRINT), 'patients.json', [
            'mime' => 'application/json',
        ]);

        return $mail;
    }
}
