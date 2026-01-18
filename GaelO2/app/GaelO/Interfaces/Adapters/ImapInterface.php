<?php

namespace App\GaelO\Interfaces\Adapters;

interface ImapInterface
{
    public function connect($mailbox = 'default'): void;
    public function readInbox(): void;
    public function isEmpty(): bool;
    public function getMessages();
    public function markAsSeen(int $index);
    public function disconnect(): void;
}
