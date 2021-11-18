<?php

namespace App\GaelO\UseCases\ExportStudyData;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService\AuthorizationUserService;
use App\GaelO\Services\ExportStudyService;
use Exception;

class ExportStudyData{

    private AuthorizationUserService $authorizationUserService;
    private ExportStudyService $exportStudyService;

    public function __construct(AuthorizationUserService $authorizationUserService, ExportStudyService $exportStudyService)
    {
        $this->authorizationUserService = $authorizationUserService;
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
            $this->exportStudyService->exportTrackerTable();
            $this->exportStudyService->exportUsersOfStudy();
            $this->exportStudyService->exportAssociatedFiles();

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

        $this->authorizationUserService->setUserId($currentUserId);
        if( ! $this->authorizationUserService->isRoleAllowed(Constants::ROLE_SUPERVISOR, $studyName)){
            throw new GaelOForbiddenException();
        }

    }
}
