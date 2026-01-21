<?php

namespace Tests\Unit\TestAdapters;

use App\GaelO\Adapters\ImapAdapter;
use Tests\TestCase;

class ImapAdapterTest extends TestCase
{
    private ImapAdapter $imapAdapter;

    protected function setUp(): void
    {
        $this->markTestSkipped('IMAP tests are skipped to avoid real server connections during testing.');
        parent::setUp();
        $this->imapAdapter = new ImapAdapter();
    }

    public function testConnect(): void
    {
        $this->imapAdapter->connect();
        $this->imapAdapter->readInbox();
        $messages = $this->imapAdapter->getMessages();
        foreach ($messages as $message) {
            $this->imapAdapter->markAsSeen($message['index']);
            dd($message);
        }
    }

    public function testDelete(): void
    {
        $this->imapAdapter->connect();
        $this->imapAdapter->readInbox();
        $messages = $this->imapAdapter->getMessages();
        foreach ($messages as $message) {
            $this->imapAdapter->deleteEmail($message['index']);
        }
    }
}
