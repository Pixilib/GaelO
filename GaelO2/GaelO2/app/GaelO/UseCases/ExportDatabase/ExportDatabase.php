<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Util;
use Exception;
use ZipArchive;

class ExportDatabase{

    private DatabaseDumperInterface $databaseDumperInterface;
    private AuthorizationService $authorizationService;
    private FrameworkInterface $frameworkInterface;

    public function __construct(DatabaseDumperInterface $databaseDumperInterface, AuthorizationService $authorizationService, FrameworkInterface $frameworkInterface) {
        $this->databaseDumperInterface = $databaseDumperInterface;
        $this->authorizationService = $authorizationService;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function execute(ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse){

        try{
            $this->checkAuthorization($exportDatabaseRequest->currentUserId);

            $zip=new ZipArchive();
            $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
            $zip->open($tempZip, ZipArchive::OVERWRITE);

            $databaseDumpedFile = $this->databaseDumperInterface->getDatabaseDumpFile();

            $date=Date('Ymd_his');
            $zip->addFile($databaseDumpedFile, "export_database_$date.sql");

            $this->addRecursivelyInZip($zip, $this->frameworkInterface::getStoragePath() );

            $zip->close();

            $exportDatabaseResponse->status = 200;
            $exportDatabaseResponse->statusText = 'OK';
            $exportDatabaseResponse->zipFile = $tempZip;
            $exportDatabaseResponse->fileName = "export_database_".$date.".zip";

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
