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
    public function readInbox()
    {
        try {
            $this->mailbox->connect();
            $inbox = $this->mailbox->inbox();
            $this->messages = $inbox->messages()->withBody()->withHeaders()->unseen()->get();
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

    public function getMessages()
    {
        for ($i = 0; $i < count($this->messages); $i++) {
            $message = $this->messages->get($i);
            $body = $message->text();
            $to = $message->to()[0]->email();
            yield [
                'index' => $i,
                'to' => $to,
                'body' => $body,
            ];
        }
    }

    public function markAsSeen(int $index)
    {
        $this->messages->get($index)->markSeen();
    }

    public function disconnect(): void
    {
        $this->mailbox->disconnect();
    }
}
