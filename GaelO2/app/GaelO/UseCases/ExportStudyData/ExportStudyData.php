<?php

namespace App\GaelO\UseCases\ExportStudyData;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\AbstractGaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationStudyService;
use App\GaelO\Services\ExportStudyService;
use Exception;

class ExportStudyData
{

    private AuthorizationStudyService $authorizationStudyService;
    private ExportStudyService $exportStudyService;

    public function __construct(AuthorizationStudyService $authorizationStudyService, ExportStudyService $exportStudyService)
    {
        $this->authorizationStudyService = $authorizationStudyService;
        $this->exportStudyService = $exportStudyService;
    }

    public function execute(ExportStudyDataRequest $exportStudyDataRequest, ExportStudyDataResponse $exportStudyDataResponse)
    {

        try {

            $studyName = $exportStudyDataRequest->studyName;

            $this->checkAuthorization($exportStudyDataRequest->currentUserId, $studyName);

            //Operation might be long, set max execution time to 30 minutes
            set_time_limit(1800);

            //Make this task continues even the users leave to prevent removing created temporary files
            ignore_user_abort(true);

            $this->exportStudyService->setStudyName($studyName);
            $this->exportStudyService->exportAll();
            $exportResults = $this->exportStudyService->getExportStudyResult();

            $exportStudyDataResponse->zipFile = $exportResults->getResultsAsZip();
            $exportStudyDataResponse->status = 200;
            $exportStudyDataResponse->statusText = 'OK';
            $exportStudyDataResponse->fileName = "export_" . $studyName . ".zip";
        } catch (AbstractGaelOException $e) {

            $exportStudyDataResponse->status = $e->statusCode;
            $exportStudyDataResponse->statusText = $e->statusText;
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
}
