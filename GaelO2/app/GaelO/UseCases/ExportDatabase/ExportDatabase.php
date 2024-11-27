<?php

namespace App\GaelO\UseCases\ExportDatabase;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Adapters\ZipStreamAdapter;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use Exception;

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
            //Operation might be long, set max execution time to 30 minutes
            set_time_limit(1800);

            $exportDatabaseResponse->status = 200;
            $exportDatabaseResponse->statusText = 'OK';
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

    public function readExport()
    {
        $date = Date('Ymd_His');
        $zipStream = new ZipStreamAdapter();
        $zipStream->init("export_database_" . $date . ".zip");
        //send stored file for this study
        try {
            $filePathSql = tempnam(ini_get('upload_tmp_dir'), 'TMPDB_');
            $this->databaseDumperInterface->createDatabaseDumpFile($filePathSql);
            $zipStream->addFileFromStream(fopen($filePathSql, 'rb'), "export_database_$date.sql");
            $files = FrameworkAdapter::getStoredFiles();
            foreach ($files as $file) {
                $fileStream = FrameworkAdapter::getFile($file, true);
                $zipStream->addFileFromStream($file, $fileStream);
            }
            $zipStream->finish();
        } finally {
            unlink($filePathSql);
        }
    }
}
