<?php

namespace App\GaelO\Interfaces\Adapters;

interface DatabaseDumperInterface {

    public function getDatabaseDumpFile() : string;

}
