<?php

namespace App\GaelO\UseCases\ExportStudyFiles;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Util;
use Exception;

class ExportStudyFiles
{

    private AuthorizationStudyService $authorizationStudyService;
    private string $studyName;

    public function __construct(AuthorizationStudyService $authorizationStudyService)
    {
        $this->authorizationStudyService = $authorizationStudyService;
    }

    public function execute(ExportStudyFilesRequest $exportStudyFilesRequest, ExportStudyFilesResponse $exportStudyFilesResponse)
    {

        try {

            #increase memory limit as php zip stores metadata file in memory until final generation of zip
            $studyName = $exportStudyFilesRequest->studyName;

            $this->checkAuthorization($exportStudyFilesRequest->currentUserId, $studyName);

            $this->studyName = $studyName;

            //Operation might be long, set max execution time to 30 minutes
            set_time_limit(1800);

            $exportStudyFilesResponse->status = 200;
            $exportStudyFilesResponse->statusText = 'OK';
            $exportStudyFilesResponse->fileName = "export_files_" . $studyName . ".zip";
        } catch (AbstractGaelOException $e) {
            $exportStudyFilesResponse->status = $e->statusCode;
            $exportStudyFilesResponse->statusText = $e->statusText;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function checkAuthorization(int $currentUserId, string $studyName)
    {

        $this->authorizationStudyService->setStudyName($studyName);
        $this->authorizationStudyService->setUserId($currentUserId);
        if (!$this->authorizationStudyService->isAllowedStudy(Constants::ROLE_SUPERVISOR)) {
            throw new GaelOForbiddenException();
        }
    }

    public function readExport(){
        Util::exportAssociatedFiles($this->studyName);
    }
}
