<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces;

class SendEmailAdapter implements MailInterface {

    public function setFrom(array $from){
        $this->from = $from;

    }

    public function setTo(array $to){
        $this->to = $to

    }

    public function setObject(string $object){
        $this->object = $object

    }

    public function setBody(string $body){
        $this->body = $body

    }

    public function sendEmail(){
        //ICI APPELER FACADE LARAVEL AVEC LA QUEUE

    }

}