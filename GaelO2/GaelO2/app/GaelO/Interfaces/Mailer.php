<?php

interface Mailer {
    
    public function setFrom(array $from);
    public function setTo(array $to);
    public function setObject(string $object);
    public function setBody(string $body);
    public function sendEmail();
}

?>