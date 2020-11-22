<?php

namespace App\GaelO\UseCases\GetDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Exceptions\GaelOForbiddenException;
use App\GaelO\Services\AuthorizationService;
use App\GaelO\Services\OrthancService;
use App\GaelO\Services\VisitService;
use Exception;

class GetDicoms{

    public function __construct(AuthorizationService $authorizationService, VisitService $visitService, OrthancService $orthancService)
    {
        $this->orthancService = $orthancService;
        $this->visitService = $visitService;
        $this->authorizationService = $authorizationService;
        $this->orthancService->setOrthancServer(false);
    }

    public function execute(GetDicomsRequest $getDicomsRequest, GetDicomsResponse $getDicomsResponse){

        try{

            //Checker Authorization
            $this->checkAuthorization($getDicomsRequest->currentUserId, $getDicomsRequest->visitId, $getDicomsRequest->role);
            //Visits data
            $visitContext = $this->visitService->getVisitContext($getDicomsRequest->visitId);
            $studyName = $visitContext['visit_type']['visit_group']['study_name'];
            $visitType = $visitContext['visit_type']['name'];
            $visitGroup =  $visitContext['visit_group']['modality'];
            $patientCode = $visitContext['patient']['patient_code'];

            //Get SeriesOrthancID from database to be downloaded
            $this->orthancSeriesIDs = $this->visitService->getVisitSeriesIdsDicomArray($getDicomsRequest->visitId, false);
            //First output the filename, then the controller will call outputStream to get content of orthanc response
            $getDicomsResponse->filename = 'DICOM_'.$studyName.'_'.$visitGroup.'_'.$visitType.'_'.$patientCode.'zip';

            $getDicomsResponse->status = 200;
            $getDicomsResponse->statusText = 'OK';

        }catch (GaelOException $e){
            $getDicomsResponse->status = $e->statusCode;
            $getDicomsResponse->statusText = $e->statusText;
            $getDicomsResponse->body = $e->getErrorBody();
        } catch (Exception $e){
            throw $e;
        }

    }

    private function checkAuthorization(int $currentUserId, int $visitId, string $role){
        $this->authorizationService->setCurrentUser($currentUserId);
        if( ! $this->authorizationService->isDicomAccessAllowedForUser($visitId, $role)){
            throw new GaelOForbiddenException();
        }
    }

    public function outputStream(){
        $this->orthancService->getOrthancZipStream($this->orthancSeriesIDs);
    }

}
