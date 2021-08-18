<?php

namespace App\GaelO\Services\StoreObjects\Export;

class ExportFile{

    private string $filename;
    private string $path;

    public function __construct(string $filename, string $path)
    {
        $this->filename = $filename;
        $this->path = $path;
    }

    public function getFilename(){
        return $this->filename;
    }

    public function getPath(){
        return $this->path;
    }
}
