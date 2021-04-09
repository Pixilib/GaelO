<?php

namespace App\GaelO\UseCases\ExportStudyData;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\ExportStudyService;
use Exception;
use ZipArchive;

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

            $zip=new ZipArchive();
            $tempZip=tempnam(ini_get('upload_tmp_dir'), 'TMP_ZIP_EXPORT_');
            $zip->open($tempZip, ZipArchive::OVERWRITE);
            $date=Date('Ymd_his');

            $this->exportStudyService->setStudyName($exportStudyDataRequest->studyName);
            $patientExportFileName = $this->exportStudyService->exportPatientTable();
            $visitTypeFileName = $this->exportStudyService->exportVisitTable();
            $dicomFileName= $this->exportStudyService->exportDicomsTable();
            $reviewFileName = $this->exportStudyService->exportReviewTable();

            $zip->addFile($patientExportFileName, "export_patients.xlsx");
            $zip->addFile($visitTypeFileName, "export_visits.xlsx");
            $zip->addFile($dicomFileName, "export_dicoms.xlsx");
            $zip->addFile($reviewFileName, "export_reviews.xlsx");
            $zip->close();

            $exportStudyDataResponse->status = 200;
            $exportStudyDataResponse->statusText = 'OK';
            $exportStudyDataResponse->zipFile = $tempZip;
            $exportStudyDataResponse->fileName = "export_".$exportStudyDataRequest->studyName."_".$date.".zip";

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
