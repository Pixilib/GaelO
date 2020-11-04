<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Adapters\DatabaseDumper;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\PathService;

use ZipArchive;

class ExportDatabase{

    public function __construct(DatabaseDumper $databaseDumper, AuthorizationService $authorizationService) {
        $this->databaseDumper = $databaseDumper;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse){

        if( $this->checkAuthorization($exportDatabaseRequest->currentUserId)) {

            $zip=new ZipArchive;
            $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
            $zip->open($tempZip, ZipArchive::CREATE);

            $databaseDumpedFile = $this->databaseDumper->getDatabaseDumpFile();

            $date=Date('Ymd_his');
            $zip->addFile($databaseDumpedFile, "export_database_$date.sql");

            $this->addRecursivelyInZip($zip, LaravelFunctionAdapter::getStoragePath() );

            $zip->close();

            $exportDatabaseResponse->status = 200;
            $exportDatabaseResponse->statusText = 'OK';
            $exportDatabaseResponse->zipFile = $tempZip;
            $exportDatabaseResponse->fileName = "export_database_".$date."zip";

        }else{
            $exportDatabaseResponse->status = 403;
            $exportDatabaseResponse->statusText = 'Forbidden';
        };



    }

    private function addRecursivelyInZip(ZipArchive $zip, String $path){

        $fileGenerator=PathService::getFileInPathGenerator($path);

        foreach ($fileGenerator as $file) {
            $filePath=$file->getRealPath();
            $subPathDestination=substr($filePath, strlen($path));
            // Add current file to archive
            $zip->addFile($filePath, $subPathDestination);

        }

    }

    private function checkAuthorization($userId) : bool {
        return $this->authorizationService->isAdmin($userId);
    }

}
