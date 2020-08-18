<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\MailInterface;

use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreated;

class SendEmailAdapter implements MailInterface {

    public function setFrom(array $from){
        $this->from = $from;
    }

    public function setTo(array $to){
        $this->to = $to;

    }

    public function setVariable(array $variables){
        $this->variables = $variables;

        /**
         * [
                'name' => 'salim',
                'username' => 'salim',
                'password' => 'salim',
                'platformName'=>'GaelO',
                'webAddress' => 'gaelo.fr',
                'corporation' => 'lysarc',
                'adminEmail' => 'salim.kanoun@gmail.com'
                ]
         */

    }

    public function setModel(){

    }

    public function setBody(string $body){
        $this->body = $body;

    }

    public function sendEmail(){
        foreach($this->to as $destinator ){
            Mail::to($destinator)->queue(new UserCreated($this->from, $this->variables)
            );

        }


    }

}
