<?php

namespace App\GaelO\UseCases\SendMail;

class SendMailResponse
{
    public int $status;
    public string $statusText;
    public $body = null;
}
