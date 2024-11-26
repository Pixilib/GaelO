<?php

namespace App\GaelO\Interfaces\Adapters;

use Psr\Http\Message\StreamInterface;

Interface ZipStreamInterface {
    public function init(string $filename): void;
    public function addFileFromString(string $filename, string $content): void;
    public function addFileFromStream(string $filename, $stream) :void;
    public function finish();
}