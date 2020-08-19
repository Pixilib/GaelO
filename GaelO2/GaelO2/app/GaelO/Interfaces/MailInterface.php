<?php

namespace App\GaelO\Interfaces;

Interface MailInterface {

    public function setReplyTo(string $replyTo);
    public function setTo(array $to);
    public function setVariable(array $variables);
    public function sendModel(int $model);
    public function setBody(string $body);
    public function sendEmail();

}

?>
