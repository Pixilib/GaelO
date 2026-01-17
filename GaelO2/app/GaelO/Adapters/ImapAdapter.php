<?php

namespace App\GaelO\Adapters;

use DirectoryTree\ImapEngine\Mailbox;

use App\GaelO\Interfaces\Adapters\ImapInterface;
use App\Jobs\JobQcReport;
use Illuminate\Support\Facades\Mail;

class ImapAdapter implements ImapInterface
{
    private Mailbox $mailbox;
    public function sendQcReportJob(int $visitId): void
    {
        $this->mailbox = new Mailbox([
            'host' => 'imap.example.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'user@example.com',
            'password' => 'password',
        ]);
    }
    public function getMessages()
    {
        $inbox = $this->mailbox->inbox();
        $messages = $inbox->messages()->get();
    }
}
