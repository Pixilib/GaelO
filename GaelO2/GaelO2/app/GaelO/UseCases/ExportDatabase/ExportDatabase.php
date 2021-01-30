<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Adapters\DatabaseDumper;
use App\GaelO\Adapters\LaravelFunctionAdapter;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\PathService;
use App\GaelO\Util;
use Exception;
use ZipArchive;

class ExportDatabase{

    private DatabaseDumper $databaseDumper;
    private AuthorizationService $authorizationService;

    public function __construct(DatabaseDumper $databaseDumper, AuthorizationService $authorizationService) {
        $this->databaseDumper = $databaseDumper;
        $this->authorizationService = $authorizationService;
    }

    public function execute(ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse){

        try{
            $this->checkAuthorization($exportDatabaseRequest->currentUserId);

            $zip=new ZipArchive();
            $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
            $zip->open($tempZip, ZipArchive::OVERWRITE);

            $databaseDumpedFile = $this->databaseDumper->getDatabaseDumpFile();

            $date=Date('Ymd_his');
            $zip->addFile($databaseDumpedFile, "export_database_$date.sql");

            $this->addRecursivelyInZip($zip, LaravelFunctionAdapter::getStoragePath() );

            $zip->close();

            $exportDatabaseResponse->status = 200;
            $exportDatabaseResponse->statusText = 'OK';
            $exportDatabaseResponse->zipFile = $tempZip;
            $exportDatabaseResponse->fileName = "export_database_".$date."zip";

        }catch(GaelOException $e){
            $exportDatabaseResponse->status = $e->statusCode;
            $exportDatabaseResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        };



    }

    private function addRecursivelyInZip(ZipArchive $zip, String $path){

        $fileGenerator=Util::getFileInPathGenerator($path);

        foreach ($fileGenerator as $file) {
            $filePath=$file->getRealPath();
            $subPathDestination=substr($filePath, strlen($path));
            // Add current file to archive
            $zip->addFile($filePath, $subPathDestination);

        }

    }

    private function checkAuthorization(int $userId) : void {
        $this->authorizationService->setCurrentUserAndRole($userId);
        if( ! $this->authorizationService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        };
    }

}
