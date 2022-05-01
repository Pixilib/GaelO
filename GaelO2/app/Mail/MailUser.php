<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailUser extends Mailable implements ShouldQueue
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
        $subject = $this->parameters['study'] ?
            $this->parameters['study'] . " - " . $this->parameters['subject'] :
            $this->parameters['subject'];

        $mail = $this->view('mails.mail_user')
            ->subject($subject)
            ->with($this->parameters);

        //If mail is associated to a patients creation request, attach given json
        array_key_exists('patients', $this->parameters) && isset($this->parameters['patients']) ?
            $mail->attachData($this->parameters['patients'], 'patients.json', [
                'mime' => 'application/json',
            ])
            : null;

        return $mail;
    }
}
