<?php

namespace App\GaelO\Interfaces\Adapters;

Interface MailInterface {

    public function setReplyTo(?string $replyTo = null);
    public function setTo(array $to);
    public function setParameters(array $parameters);
    public function setBody($body);
    public function send();

}
