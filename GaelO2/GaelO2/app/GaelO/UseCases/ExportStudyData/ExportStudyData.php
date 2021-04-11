<?php

namespace App\GaelO\UseCases\ExportStudyData;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\ExportStudyService;
use Exception;

class ExportStudyData{

    private AuthorizationService $authorizationService;
    private ExportStudyService $exportStudyService;

    public function __construct(AuthorizationService $authorizationService, ExportStudyService $exportStudyService)
    {
        $this->authorizationService = $authorizationService;
        $this->exportStudyService = $exportStudyService;
    }

    public function execute(ExportStudyDataRequest $exportStudyDataRequest, ExportStudyDataResponse $exportStudyDataResponse){

        try{

            $this->checkAuthorization($exportStudyDataRequest->currentUserId, $exportStudyDataRequest->studyName);

            $this->exportStudyService->setStudyName($exportStudyDataRequest->studyName);

            $this->exportStudyService->exportPatientTable();
            $this->exportStudyService->exportVisitTable();
            $this->exportStudyService->exportDicomsTable();
            $this->exportStudyService->exportReviewTable();

            $exportResults = $this->exportStudyService->getExportStudyResult();

            $exportStudyDataResponse->zipFile = $exportResults->getResultsAsZip();
            $exportStudyDataResponse->status = 200;
            $exportStudyDataResponse->statusText = 'OK';
            $exportStudyDataResponse->fileName = "export_".$exportStudyDataRequest->studyName.".zip";

        }catch(GaelOException $e){

            $exportStudyDataResponse->status = $e->statusCode;
            $exportStudyDataResponse->statusText = $e->statusText;

        }catch (Exception $e){
            throw $e;
        };

    }

    private function checkAuthorization(int $currentUserId, string $studyName){

        $this->authorizationService->setCurrentUserAndRole($currentUserId, Constants::ROLE_SUPERVISOR);
        if( ! $this->authorizationService->isRoleAllowed($studyName)){
            throw new GaelOForbiddenException();
        }

    }
}
