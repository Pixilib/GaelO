<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Util;
use Exception;
use ZipArchive;

class ExportDatabase
{

    private DatabaseDumperInterface $databaseDumperInterface;
    private AuthorizationUserService $authorizationUserService;

    public function __construct(DatabaseDumperInterface $databaseDumperInterface, AuthorizationUserService $authorizationUserService)
    {
        $this->databaseDumperInterface = $databaseDumperInterface;
        $this->authorizationUserService = $authorizationUserService;
    }

    public function execute(ExportDatabaseRequest $exportDatabaseRequest, ExportDatabaseResponse $exportDatabaseResponse)
    {

        try {
            $this->checkAuthorization($exportDatabaseRequest->currentUserId);

            $zip = new ZipArchive();
            $tempZip = tempnam(ini_get('upload_tmp_dir'), 'TMPZIPDB_');
            $zip->open($tempZip, ZipArchive::OVERWRITE);

            $filePathSql = tempnam(ini_get('upload_tmp_dir'), 'TMPDB_');
            $this->databaseDumperInterface->createDatabaseDumpFile($filePathSql);

            $date = Date('Ymd_His');
            
            $zip->addFile($filePathSql, "export_database_$date.sql");
            unlink($filePathSql);

            Util::addStoredFilesInZip($zip, null);

            $zip->close();

            $exportDatabaseResponse->status = 200;
            $exportDatabaseResponse->statusText = 'OK';
            $exportDatabaseResponse->zipFile = $tempZip;
            $exportDatabaseResponse->fileName = "export_database_" . $date . ".zip";
        } catch (AbstractGaelOException $e) {
            $exportDatabaseResponse->status = $e->statusCode;
            $exportDatabaseResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $userId): void
    {
        $this->authorizationUserService->setUserId($userId);
        if (!$this->authorizationUserService->isAdmin($userId)) {
            throw new GaelOForbiddenException();
        }
    }
}
