<?php

namespace App\GaelO\Interfaces\Adapters;

interface DatabaseDumperInterface {

    public function createDatabaseDumpFile(string $filePath) : void;

}
