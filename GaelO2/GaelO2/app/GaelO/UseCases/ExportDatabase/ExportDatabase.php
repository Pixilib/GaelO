<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Adapters\DatabaseDumper;

class ExportDatabase{

    public function __construct(DatabaseDumper $databaseDumper) {
        $this->databaseDumper = $databaseDumper;

    }

    public function exceute(){

        $zip=new \ZipArchive;
        $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
        $zip->open($tempZip, \ZipArchive::CREATE);

        $databaseDumpedFile = $this->databaseDumper->getDatabaseDumpFile();

        $date=Date('Ymd_his');
        $zip->addFile($databaseDumpedFile, "export_database_$date.sql");

    }

}
