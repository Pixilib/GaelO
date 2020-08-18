<?php

namespace App\GaelO\Interfaces;

Interface MailInterface {

    public function setFrom(array $from);
    public function setTo(array $to);
    public function setVariable(array $variables);
    public function setBody(string $body);
    public function sendEmail();
}

?>
