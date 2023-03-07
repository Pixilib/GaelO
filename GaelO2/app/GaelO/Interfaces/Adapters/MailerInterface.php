<?php

namespace App\GaelO\Interfaces\Adapters;

Interface MailerInterface {

    public function setReplyTo(?string $replyTo = null);
    public function setTo(array $to);
    public function setParameters(array $parameters);
    public function setBody(int $body);
    public function send();

}
