<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\ZipStreamInterface;
use Psr\Http\Message\StreamInterface;
use ZipStream;

class ZipStreamAdapter implements ZipStreamInterface
{

    private ZipStream\ZipStream $zipStream;

    public function init(string $filename): void
    {
        $this->zipStream = new ZipStream\ZipStream(
            outputName: $filename,
            sendHttpHeaders: true,
        );
    }

    public function addFileFromString(string $filename, string $content): void
    {
        $this->zipStream->addFile(
            fileName: $filename,
            data: $content,
        );
    }

    public function addFileFromStream(string $filename, $stream): void
    {
        $this->zipStream->addFileFromStream(
            fileName: $filename,
            stream: $stream,
        );
    }

    public function finish()
    {
        return $this->zipStream->finish();
    }
}
