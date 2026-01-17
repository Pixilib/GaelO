<?php

namespace App\GaelO\Adapters;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Mailbox;

use DirectoryTree\ImapEngine\Laravel\Facades\Imap;
use DirectoryTree\ImapEngine\Exceptions\ImapCommandException;
use DirectoryTree\ImapEngine\Exceptions\ImapConnectionException;
use Illuminate\Support\Facades\Log;

class ImapAdapter
{
    private Mailbox $mailbox;
    private MessageCollection $messages;

    public function connect($mailbox = 'default'): void
    {
        $this->mailbox = Imap::mailbox($mailbox);
    }
    public function getMessages()
    {
        try {
            $this->mailbox->connect();
            $inbox = $this->mailbox->inbox();
            $this->messages = $inbox->messages()->withBody()->withHeaders()->get();
        } catch (ImapCommandException $e) {
            Log::error('IMAP Command Exception: ' . $e->getMessage());
        } catch (ImapConnectionException $e) {
            Log::error('IMAP Connection Exception: ' . $e->getMessage());
        }
    }

    public function isEmpty(): bool
    {
        return $this->messages->count() === 0;
    }

    public function getMessage(bool $markAsSeen)
    {
        foreach ($this->messages as $message) {
            $header = $message->headers();
            $body = $message->body();
            $to = $message->to()[0]->email();
            if ($markAsSeen) $message->markSeen();
            yield [
                'to' => $to,
                'header' => $header,
                'body' => $body,
            ];
        }
    }

    public function disconnect(): void
    {
        $this->mailbox->disconnect();
    }
}
