<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\ImapInterface;
use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Mailbox;

use DirectoryTree\ImapEngine\Laravel\Facades\Imap;
use DirectoryTree\ImapEngine\Exceptions\ImapCommandException;
use DirectoryTree\ImapEngine\Exceptions\ImapConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImapAdapter implements ImapInterface
{
    private Mailbox $mailbox;
    private MessageCollection $messages;

    public function connect($mailbox = 'default'): void
    {
        $this->mailbox = Imap::mailbox($mailbox);
    }
    public function readInbox(): void
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
            $date = $message->date()->toISOString();
            yield [
                'index' => $i,
                'to' => $to,
                'body' => $body,
                'date' => $date,
            ];
        }
    }

    public function markAsSeen(int $index): void
    {
        try {
            $this->messages->get($index)->markSeen();
        } catch (Throwable $e) {
            Log::error('Error marking email as seen: ' . $e->getMessage());
        }
    }

    public function deleteEmail(int $index): void
    {
        try {
            $this->messages->get($index)->delete();
        } catch (Throwable $e) {
            Log::error('Error deleting email: ' . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        try {
            $this->mailbox->disconnect();
        } catch (Throwable $e) {
            Log::error('Error disconnecting from IMAP: ' . $e->getMessage());
        }
    }
}
