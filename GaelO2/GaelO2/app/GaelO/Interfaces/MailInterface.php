<?php

namespace App\GaelO\Interfaces;

Interface MailInterface {

    public function setReplyTo(?string $replyTo = null);
    public function setTo(array $to);
    public function setParameters(array $parameters);
    public function sendModel(int $model);
    public function setBody(string $body);
    public function sendEmail();

}

?>
